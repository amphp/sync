<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\Mutex;

/**
 * @requires extension pthreads
 */
abstract class AbstractMutexTest extends AsyncTestCase
{
    /**
     * @return \Amp\Sync\Mutex
     */
    abstract public function createMutex(): Mutex;

    public function testAcquire(): \Generator
    {
        $mutex = $this->createMutex();
        $lock = yield $mutex->acquire();
        $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testAcquireMultiple(): \Generator
    {
        $this->setMinimumRuntime(300);

        $mutex = $this->createMutex();

        $lock1 = yield $mutex->acquire();
        Loop::delay(100, function () use ($lock1) {
            $lock1->release();
        });

        $lock2 = yield $mutex->acquire();
        Loop::delay(100, function () use ($lock2) {
            $lock2->release();
        });

        $lock3 = yield $mutex->acquire();
        Loop::delay(100, function () use ($lock3) {
            $lock3->release();
        });
    }
}
