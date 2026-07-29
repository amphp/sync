<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\PHPUnit\AsyncTestCase;
use Amp\TimeoutCancellation;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

final class SynchronizedTest extends AsyncTestCase
{
    public function testSynchronized(): void
    {
        $this->setMinimumRuntime(0.3);

        $mutex = new LocalMutex();
        $callback = function (int $value): int {
            delay(0.1);

            return $value;
        };

        $futures = [];
        foreach ([0, 1, 2] as $value) {
            $futures[] = async(fn () => synchronized($mutex, fn () => $callback($value)));
        }

        self::assertEquals([0, 1, 2], await($futures));
    }

    public function testSynchronizedReentry(): void
    {
        $mutex = new LocalMutex();
        $count = 0;

        synchronized($mutex, function () use ($mutex, &$count) {
            $count++;

            synchronized($mutex, function () use (&$count) {
                $count++;
            });
        });

        self::assertSame(2, $count);
    }

    public function testSynchronizedReentryAsync(): void
    {
        $mutex = new LocalMutex();
        $count = 0;

        synchronized($mutex, function () use ($mutex, &$count) {
            async(function () use ($mutex, &$count) {
                synchronized($mutex, function () use (&$count) {
                    $count++;
                });
            });

            delay(1);

            $count = 10;
        });

        delay(2);

        // The async synchronized block must be executed after $count = 10 is executed
        self::assertSame(11, $count);
    }

    public function testSynchronizedReentryDifferentLocks(): void
    {
        $mutexA = new LocalMutex();
        $mutexB = new LocalMutex();

        $lock = $mutexB->acquire();

        $op = async(function () use ($mutexA, $mutexB) {
            print 'Before ';

            synchronized($mutexA, function () use ($mutexB) {
                print 'before ';

                synchronized($mutexB, function () {
                    print 'X ';
                });

                print 'after ';
            });

            print 'After ';
        });

        delay(1);

        print 'Unlock ';

        $lock->release();

        $op->await();

        self::expectOutputString('Before before Unlock X after After ');
    }

    public function testSynchronizedPassesCancellationToClosure(): void
    {
        $mutex = new LocalMutex();
        $cancellation = new TimeoutCancellation(1);

        $received = synchronized($mutex, function (?Cancellation $outer) use ($mutex, $cancellation): array {
            $inner = synchronized($mutex, fn (?Cancellation $c) => $c, $cancellation);
            return [$outer, $inner];
        }, $cancellation);

        self::assertSame([$cancellation, $cancellation], $received);
    }

    public function testSynchronizedCancellation(): void
    {
        $mutex = new LocalMutex();

        $future = async(fn () => synchronized($mutex, function (): int {
            delay(0.3);
            return 1;
        }));

        delay(0.1); // Wait for the closure above to acquire the lock.

        try {
            $this->expectException(CancelledException::class);
            synchronized($mutex, fn () => 2, new TimeoutCancellation(0.1));
        } finally {
            self::assertSame(1, $future->await());
        }
    }

    public function testSynchronizedAfterCancellation(): void
    {
        $mutex = new LocalMutex();

        $future = async(fn () => synchronized($mutex, function (): int {
            delay(0.3);
            return 1;
        }));

        delay(0.1); // Wait for the closure above to acquire the lock.

        try {
            synchronized($mutex, fn () => 2, new TimeoutCancellation(0.1));
            self::fail('The blocked synchronized() call should have been cancelled');
        } catch (CancelledException) {
            // Expected.
        }

        self::assertSame(1, $future->await());

        self::assertSame(3, synchronized($mutex, fn () => 3));
    }
}
