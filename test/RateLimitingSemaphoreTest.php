<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\Future;
use Amp\Pipeline\Pipeline;
use Revolt\EventLoop;
use function Amp\async;
use function Amp\delay;

class RateLimitingSemaphoreTest extends AbstractSemaphoreTest
{
    private const LOCK_PERIOD = 0.2;

    #[\Override]
    protected function createSemaphore(int $locks): Semaphore
    {
        return new RateLimitingSemaphore(new LocalSemaphore($locks), self::LOCK_PERIOD);
    }

    public function providePeriods(): iterable
    {
        yield 'one-lock-no-delay' => [0, 3, 1];
        yield 'one-lock-with-delay' => [0.1, 3, 1];
        yield 'multiple-locks-no-delay' => [0, 10, 3];
        yield 'multiple-locks-with-delay' => [ 0.1, 10, 3];
        yield 'multiple-locks-long-delay' => [0.5, 10, 3];
    }

    /**
     * @dataProvider providePeriods
     */
    public function testSemaphore(float $delay, int $cycles, int $numLocks): void
    {
        if ($cycles < $numLocks) {
            $this->fail('Must have more locks than test cycles');
        }

        $semaphore = new RateLimitingSemaphore(new LocalSemaphore($numLocks), self::LOCK_PERIOD);

        $this->setMinimumRuntime(\max(self::LOCK_PERIOD * (($cycles - 1) / $numLocks), $delay * $cycles));

        Pipeline::fromIterable(\range(1, $cycles))
            ->delay($delay)
            ->forEach(fn () => $semaphore->acquire()->release());
    }

    public function testInvalidLockPeriod(): void
    {
        $this->expectException(\ValueError::class);
        new RateLimitingSemaphore(new LocalSemaphore(1), 0);
    }

    public function testLockNotAvailableUntilPeriodElapsed(): void
    {
        $semaphore = new RateLimitingSemaphore(new LocalSemaphore(1), self::LOCK_PERIOD);

        // Consume and immediately release the only lock.
        $semaphore->acquire()->release();

        // The released lock does not become available again until the lock period has elapsed.
        $this->setMinimumRuntime(self::LOCK_PERIOD);
        $this->setTimeout(self::LOCK_PERIOD + 0.1);

        $semaphore->acquire()->release();
    }

    public function testReleaseDoesNotMakeLockAvailableEarly(): void
    {
        $semaphore = new RateLimitingSemaphore(new LocalSemaphore(1), self::LOCK_PERIOD);

        $semaphore->acquire()->release();

        $future = async($semaphore->acquire(...));

        // Lock should not be available until the lock period has elapsed.
        delay(self::LOCK_PERIOD / 2);
        self::assertFalse($future->isComplete());

        delay(self::LOCK_PERIOD / 2);
        self::assertTrue($future->isComplete());
    }

    public function testCancelledAcquireDoesNotCorruptRateLimiting(): void
    {
        $semaphore = new RateLimitingSemaphore(new LocalSemaphore(1), self::LOCK_PERIOD);

        $lock = $semaphore->acquire();

        $deferredCancellation = new DeferredCancellation();
        EventLoop::delay(self::LOCK_PERIOD / 2, static fn () => $deferredCancellation->cancel());

        try {
            $semaphore->acquire($deferredCancellation->getCancellation());
            self::fail('The blocked acquire should have been cancelled');
        } catch (CancelledException) {
            // Expected.
        }

        $lock->release();

        $future = async($semaphore->acquire(...));

        delay(self::LOCK_PERIOD / 2);
        self::assertFalse($future->isComplete());

        delay(self::LOCK_PERIOD / 2);
        self::assertTrue($future->isComplete());
    }

    public function testUnderlyingAcquireExceptionPropagates(): void
    {
        $mock = $this->createMock(Semaphore::class);

        $failing = true;

        $mock->expects(self::exactly(2))
            ->method('acquire')
            ->willReturnCallback(function () use (&$failing): Lock {
                if ($failing) {
                    throw new SyncException('Acquire failed');
                }

                return new Lock(static fn () => null);
            });

        $semaphore = new RateLimitingSemaphore($mock, 0.1);

        try {
            $semaphore->acquire();
            self::fail('Expected the underlying SyncException to propagate');
        } catch (SyncException) {
            // Expected.
        }

        $failing = false;

        $lock = $semaphore->acquire();
        self::assertFalse($lock->isReleased());
        $lock->release();
    }

    public function provideLockCounts(): iterable
    {
        yield 'single-lock' => [1];
        yield 'two-locks' => [2];
        yield 'several-locks' => [5];
        yield 'many-locks' => [20];
    }

    /**
     * @dataProvider provideLockCounts
     */
    public function testMultipleLocksBecomeAvailableAfterPeriod(int $lockCount): void
    {
        $semaphore = new RateLimitingSemaphore(new LocalSemaphore($lockCount), self::LOCK_PERIOD);

        // Exhaust and release every lock, so all capacity is in its cooldown period.
        $futures = [];
        for ($i = 0; $i < $lockCount; $i++) {
            $futures[] = async($semaphore->acquire(...))
                ->map(static fn (Lock $lock) => $lock->release());
        }
        Future\await($futures);

        // Queue a waiter for each lock.
        $futures = [];
        for ($i = 0; $i < $lockCount; $i++) {
            $futures[] = async($semaphore->acquire(...));
        }

        // Before the cooldown elapses, none of the locks are available.
        delay(self::LOCK_PERIOD / 2);
        foreach ($futures as $future) {
            self::assertFalse($future->isComplete());
        }

        // Once the cooldown elapses, every lock becomes available again.
        delay(self::LOCK_PERIOD / 2);
        foreach ($futures as $future) {
            self::assertTrue($future->isComplete());
        }
    }

    /**
     * @dataProvider provideLockCounts
     */
    public function testStaggeredAcquisitionOfLocks(int $lockCount): void
    {
        $initialAcquireCount = (int) \ceil($lockCount / 2);
        $semaphore = new RateLimitingSemaphore(new LocalSemaphore($lockCount), self::LOCK_PERIOD);

        $futures = [];
        for ($i = 0; $i < $initialAcquireCount; $i++) {
            $futures[] = async($semaphore->acquire(...))
                ->map(static fn (Lock $lock) => $lock->release());
        }
        Future\await($futures);

        $futures = [];
        for ($i = 0; $i < $lockCount; $i++) {
            $futures[] = async($semaphore->acquire(...));
        }

        // Before the cooldown elapses, only $lockCount - $initialAcquireCount locks are available.
        delay(self::LOCK_PERIOD / 2);
        $completeCount = 0;
        foreach ($futures as $future) {
            if ($future->isComplete()) {
                $completeCount++;
            }
        }
        self::assertSame($lockCount - $initialAcquireCount, $completeCount);

        // Once the cooldown elapses, every lock becomes available again.
        delay(self::LOCK_PERIOD / 2);
        foreach ($futures as $future) {
            self::assertTrue($future->isComplete());
        }
    }
}
