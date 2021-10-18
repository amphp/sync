<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\KeyedMutex;
use Revolt\EventLoop;
use function Amp\delay;

abstract class AbstractKeyedMutexTest extends AsyncTestCase
{
    abstract public function createMutex(): KeyedMutex;

    public function testAcquire(): void
    {
        $mutex = $this->createMutex();
        $lock = $mutex->acquire('test');
        $lock->release();
        self::assertTrue($lock->isReleased());
    }

    public function testAcquireMultiple(): void
    {
        $this->setMinimumRuntime(0.3);

        $mutex = $this->createMutex();

        $lock1 = $mutex->acquire('test');
        EventLoop::queue(function () use ($lock1): void {
            delay(0.1);
            $lock1->release();
        });

        $lock2 = $mutex->acquire('test');
        EventLoop::queue(function () use ($lock2): void {
            delay(0.1);
            $lock2->release();
        });

        $lock3 = $mutex->acquire('test');
        delay(0.1);
        $lock3->release();
    }
}
