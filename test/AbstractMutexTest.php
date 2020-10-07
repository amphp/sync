<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\Mutex;
use function Amp\defer;
use function Amp\delay;

abstract class AbstractMutexTest extends AsyncTestCase
{
    abstract public function createMutex(): Mutex;

    public function testAcquire(): void
    {
        $mutex = $this->createMutex();
        $lock = $mutex->acquire();
        $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testAcquireMultiple(): void
    {
        $this->setMinimumRuntime(300);

        $mutex = $this->createMutex();

        $lock1 = $mutex->acquire();
        defer(function () use ($lock1): void {
            delay(100);
            $lock1->release();
        });

        $lock2 = $mutex->acquire();
        defer(function () use ($lock2): void {
            delay(100);
            $lock2->release();
        });

        $lock3 = $mutex->acquire();
        delay(100);
        $lock3->release();    }
}
