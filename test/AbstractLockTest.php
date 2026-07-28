<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\PHPUnit\AsyncTestCase;
use Amp\TimeoutCancellation;
use Revolt\EventLoop;
use function Amp\delay;

abstract class AbstractLockTest extends AsyncTestCase
{
    abstract protected function acquire(?Cancellation $cancellation = null): Lock;

    public function testAcquireWithUnusedCancellation(): void
    {
        $lock = $this->acquire(new TimeoutCancellation(0.1));
        self::assertFalse($lock->isReleased());
        $lock->release();
        self::assertTrue($lock->isReleased());
    }

    public function testAcquireCancellation(): void
    {
        $lock = $this->acquire();

        // Keep the lock held (and the event loop alive) beyond the cancellation timeout.
        EventLoop::queue(function () use ($lock): void {
            delay(0.2);
            $lock->release();
        });

        $this->expectException(CancelledException::class);
        $this->acquire(new TimeoutCancellation(0.1));
    }

    public function testAcquireAfterCancelledAcquire(): void
    {
        $lock1 = $this->acquire();

        // Release the first lock after the pending acquire below has been cancelled.
        EventLoop::queue(function () use ($lock1): void {
            delay(0.2);
            $lock1->release();
        });

        try {
            $this->acquire(new TimeoutCancellation(0.1));
            self::fail('The pending acquire should have been cancelled');
        } catch (CancelledException) {
            // Expected.
        }

        // Acquiring again must succeed once the first lock is released.
        $lock2 = $this->acquire(new TimeoutCancellation(1));
        self::assertFalse($lock2->isReleased());
        $lock2->release();
    }
}
