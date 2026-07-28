<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Revolt\EventLoop;
use function Amp\delay;

abstract class AbstractKeyedSemaphoreTest extends AbstractLockTest
{
    private ?KeyedSemaphore $semaphore = null;

    abstract protected function createSemaphore(int $size): KeyedSemaphore;

    #[\Override]
    protected function acquire(?Cancellation $cancellation = null): Lock
    {
        return ($this->semaphore ??= $this->createSemaphore(1))->acquire('test', $cancellation);
    }

    public function testAcquire(): void
    {
        $mutex = $this->createSemaphore(1);
        $lock = $mutex->acquire('test');
        $lock->release();
        self::assertTrue($lock->isReleased());
    }

    public function testAcquireMultiple(): void
    {
        $this->setMinimumRuntime(0.3);
        $this->setTimeout(0.6);

        $mutex = $this->createSemaphore(5);

        for ($i = 0; $i < 15; $i++) {
            $lock = $mutex->acquire('test');
            EventLoop::queue(function () use ($lock): void {
                delay(0.1);
                $lock->release();
            });
        }

        delay(0.1); // Wait for locks to be released.
    }
}
