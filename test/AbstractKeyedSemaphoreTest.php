<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\KeyedSemaphore;
use function Revolt\EventLoop\defer;
use function Revolt\EventLoop\delay;

abstract class AbstractKeyedSemaphoreTest extends AsyncTestCase
{
    abstract public function createSemaphore(int $size): KeyedSemaphore;

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
        $this->setTimeout(0.5);

        $mutex = $this->createSemaphore(5);

        for ($i = 0; $i < 15; $i++) {
            $lock = $mutex->acquire('test');
            defer(function () use ($lock): void {
                delay(0.1);
                $lock->release();
            });
        }

        delay(0.1); // Wait for locks to be released.
    }
}
