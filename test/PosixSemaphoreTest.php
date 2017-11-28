<?php

namespace Amp\Sync\Test;

use Amp\Delayed;
use Amp\Loop;
use Amp\Sync\PosixSemaphore;
use Amp\Sync\Semaphore;

/**
 * @group posix
 * @requires extension sysvmsg
 */
class PosixSemaphoreTest extends AbstractSemaphoreTest {
    const ID = __CLASS__;

    public function makeId(): string {
        return \spl_object_hash($this);
    }

    /**
     * @param $locks
     *
     * @return \Amp\Sync\PosixSemaphore
     */
    public function createSemaphore(int $locks): Semaphore {
        return PosixSemaphore::create(self::ID, $locks);
    }

    public function testUse() {
        $this->assertRunTimeGreaterThan(function () {
            $this->semaphore = $this->createSemaphore(1);

            Loop::run(function () {
                $used = PosixSemaphore::use(self::ID);

                $promise1 = $used->acquire();
                $promise2 = $this->semaphore->acquire();

                Loop::delay(500, function () use ($promise1) {
                    (yield $promise1)->release();
                });

                (yield $promise2)->release();
            });
        }, 500);
    }

    public function testSerializedIsSameSemaphore() {
        $this->assertRunTimeGreaterThan(function () {
            $this->semaphore = $this->createSemaphore(1);

            Loop::run(function () {
                $unserialized = \unserialize(\serialize($this->semaphore));

                $promise1 = $unserialized->acquire();
                $promise2 = $this->semaphore->acquire();

                Loop::delay(500, function () use ($promise1) {
                    (yield $promise1)->release();
                });

                (yield $promise2)->release();
            });
        }, 500);
    }

    /**
     * @depends testUse
     */
    public function testInFork() {
        $this->assertRunTimeGreaterThan(function () {
            $this->semaphore = $this->createSemaphore(1);

            $this->doInFork(function () {
                Loop::run(function () {
                    $semaphore = PosixSemaphore::use(self::ID);
                    $lock = yield $semaphore->acquire();
                    yield new Delayed(500);
                    $lock->release();
                });
            });

            Loop::run(function () {
                $lock = yield $this->semaphore->acquire();
                $lock->release();
            });
        }, 500);
    }
}
