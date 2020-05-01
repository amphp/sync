<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\KeyedMutex;

abstract class AbstractKeyedMutexTest extends AsyncTestCase
{
    abstract public function createMutex(): KeyedMutex;

    public function testAcquire(): \Generator
    {
        $mutex = $this->createMutex();
        $lock = yield $mutex->acquire('test');
        $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testAcquireMultiple(): \Generator
    {
        $this->setMinimumRuntime(300);

        $mutex = $this->createMutex();

        $lock1 = yield $mutex->acquire('test');
        Loop::delay(100, function () use ($lock1) {
            $lock1->release();
        });

        $lock2 = yield $mutex->acquire('test');
        Loop::delay(100, function () use ($lock2) {
            $lock2->release();
        });

        $lock3 = yield $mutex->acquire('test');
        Loop::delay(100, function () use ($lock3) {
            $lock3->release();
        });
    }
}
