<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\KeyedSemaphore;

abstract class AbstractKeyedSemaphoreTest extends AsyncTestCase
{
    abstract public function createSemaphore(int $size): KeyedSemaphore;

    public function testAcquire(): \Generator
    {
        $mutex = $this->createSemaphore(1);
        $lock = yield $mutex->acquire('test');
        $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testAcquireMultiple(): \Generator
    {
        $this->setMinimumRuntime(300);
        $this->setTimeout(500);

        $mutex = $this->createSemaphore(5);

        for ($i = 0; $i < 15; $i++) {
            $lock = yield $mutex->acquire('test');
            Loop::delay(100, function () use ($lock) {
                $lock->release();
            });
        }
    }
}
