<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Revolt\EventLoop;
use function Amp\delay;

abstract class AbstractMutexTest extends AbstractLockTest
{
    private ?Mutex $mutex = null;

    abstract protected function createMutex(): Mutex;

    #[\Override]
    protected function acquire(?Cancellation $cancellation = null): Lock
    {
        return ($this->mutex ??= $this->createMutex())->acquire($cancellation);
    }

    public function testAcquire(): void
    {
        $mutex = $this->createMutex();
        $lock = $mutex->acquire();
        $lock->release();
        self::assertTrue($lock->isReleased());
    }

    public function testAcquireMultiple(): void
    {
        $this->setMinimumRuntime(0.3);

        $mutex = $this->createMutex();

        $lock1 = $mutex->acquire();
        EventLoop::queue(function () use ($lock1): void {
            delay(0.1);
            $lock1->release();
        });

        $lock2 = $mutex->acquire();
        EventLoop::queue(function () use ($lock2): void {
            delay(0.1);
            $lock2->release();
        });

        $lock3 = $mutex->acquire();
        delay(0.1);
        $lock3->release();
    }
}
