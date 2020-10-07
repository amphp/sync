<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\KeyedSemaphore;
use function Amp\defer;
use function Amp\delay;

abstract class AbstractKeyedSemaphoreTest extends AsyncTestCase
{
    abstract public function createSemaphore(int $size): KeyedSemaphore;

    public function testAcquire(): void
    {
        $mutex = $this->createSemaphore(1);
        $lock = $mutex->acquire('test');
        $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testAcquireMultiple(): void
    {
        $this->setMinimumRuntime(300);
        $this->setTimeout(500);

        $mutex = $this->createSemaphore(5);

        for ($i = 0; $i < 15; $i++) {
            $lock = $mutex->acquire('test');
            defer(function () use ($lock): void {
                delay(100);
                $lock->release();
            });
        }

        delay(100); // Wait for locks to be released.
    }
}
