<?php

namespace Amp\Sync\Test;

use Amp\Delayed;
use Amp\Loop;
use Amp\Sync\PosixSemaphore;
use Amp\Sync\Semaphore;
use Amp\Sync\SyncException;

/**
 * @group posix
 * @requires extension sysvmsg
 */
class PosixSemaphoreTest extends AbstractSemaphoreTest
{
    const ID = __CLASS__;

    public function makeId(): string
    {
        return \spl_object_hash($this);
    }

    /**
     * @param $locks
     *
     * @return \Amp\Sync\PosixSemaphore
     */
    public function createSemaphore(int $locks): Semaphore
    {
        return PosixSemaphore::create(self::ID, $locks);
    }

    public function testConstructorOnInvalidMaxLocks()
    {
        Loop::run(function () {
            $this->expectException(\Error::class);
            $this->expectExceptionMessage("Number of locks must be greater than 0");

            $this->semaphore = $this->createSemaphore(-1);
        });
    }

    public function testCreateOnInvalidMaxLocks()
    {
        Loop::run(function () {
            $this->expectException(\Error::class);

            PosixSemaphore::create(self::ID, -1);
        });
    }

    public function testGetPermissions()
    {
        $this->semaphore = PosixSemaphore::create(self::ID, 1);
        Loop::run(function () {
            $used = PosixSemaphore::use(self::ID);
            $used->setPermissions(0644);

            $this->assertSame(0644, $this->semaphore->getPermissions());
        });
    }

    public function testGetId()
    {
        Loop::run(function () {
            $this->semaphore = $this->createSemaphore(1);

            $this->assertSame(PosixSemaphoreTest::class, $this->semaphore->getId());
        });
    }

    public function testUseOnInvalidSemaphoreId()
    {
        Loop::run(function () {
            $this->expectException(SyncException::class);
            $this->expectExceptionMessage("No semaphore with that ID found");

            PosixSemaphore::use(1);
        });
    }

    public function testCreateOnDuplicatedSemaphoreId()
    {
        $this->expectException(SyncException::class);
        $this->expectExceptionMessage("A semaphore with that ID already exists");

        $semaphore = PosixSemaphore::create(self::ID, 1);
        $semaphore::create(self::ID, 1);
    }

    public function testUse()
    {
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

    /**
     * @depends testUse
     */
    public function testInFork()
    {
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
