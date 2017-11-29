<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\PHPUnit\TestCase;
use Amp\Sync\Mutex;

/**
 * @requires extension pthreads
 */
abstract class AbstractMutexTest extends TestCase {
    /**
     * @return \Amp\Sync\Mutex
     */
    abstract public function createMutex(): Mutex;

    public function testAcquire() {
        Loop::run(function () {
            $mutex = $this->createMutex();
            $lock = yield $mutex->acquire();
            $lock->release();
            $this->assertTrue($lock->isReleased());
        });
    }

    public function testAcquireMultiple() {
        $this->assertRunTimeGreaterThan(function () {
            Loop::run(function () {
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
            });
        }, 300);
    }
}
