<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\PHPUnit\TestCase;
use Amp\Sync\Semaphore;

abstract class AbstractSemaphoreTest extends TestCase {
    /**
     * @var \Amp\Sync\Semaphore
     */
    protected $semaphore;

    /**
     * @param int $locks Number of locks in the semaphore.
     *
     * @return \Amp\Sync\Semaphore
     */
    abstract public function createSemaphore(int $locks): Semaphore;

    public function tearDown() {
        $this->semaphore = null; // Force Semaphore::__destruct() to be invoked.
    }

    public function testConstructorOnInvalidMaxLocks() {
        Loop::run(function () {
            $this->expectException(\Error::class);
            $this->expectExceptionMessage("The number of locks must be greater than 0");

            $this->semaphore = $this->createSemaphore(-1);
        });
    }

    public function testAcquire() {
        Loop::run(function () {
            $this->semaphore = $this->createSemaphore(1);

            $lock = yield $this->semaphore->acquire();

            $this->assertFalse($lock->isReleased());

            $lock->release();

            $this->assertTrue($lock->isReleased());
        });
    }

    public function testAcquireMultipleFromSingleLockSemaphore() {
        $this->assertRunTimeGreaterThan(function () {
            $this->semaphore = $this->createSemaphore(1);

            Loop::run(function () {
                $lock1 = yield $this->semaphore->acquire();
                $this->assertSame(0, $lock1->getId());
                Loop::delay(100, function () use ($lock1) {
                    $lock1->release();
                });

                $lock2 = yield $this->semaphore->acquire();
                $this->assertSame(0, $lock2->getId());
                Loop::delay(100, function () use ($lock2) {
                    $lock2->release();
                });

                $lock3 = yield $this->semaphore->acquire();
                $this->assertSame(0, $lock3->getId());
                Loop::delay(100, function () use ($lock3) {
                    $lock3->release();
                });
            });
        }, 300);
    }

    public function testAcquireMultipleFromMultipleLockSemaphore() {
        $this->assertRunTimeGreaterThan(function () {
            $this->semaphore = $this->createSemaphore(3);

            Loop::run(function () {
                $lock1 = yield $this->semaphore->acquire();
                Loop::delay(100, function () use ($lock1) {
                    $lock1->release();
                });

                $lock2 = yield $this->semaphore->acquire();
                $this->assertNotSame($lock1->getId(), $lock2->getId());
                Loop::delay(200, function () use ($lock2) {
                    $lock2->release();
                });

                $lock3 = yield $this->semaphore->acquire();
                $this->assertNotSame($lock1->getId(), $lock3->getId());
                $this->assertNotSame($lock2->getId(), $lock3->getId());
                Loop::delay(200, function () use ($lock3) {
                    $lock3->release();
                });

                $lock4 = yield $this->semaphore->acquire();
                $this->assertSame($lock1->getId(), $lock4->getId());
                Loop::delay(200, function () use ($lock4) {
                    $lock4->release();
                });
            });
        }, 300);
    }

    public function getSemaphoreSizes(): array {
        return [
            [5],
            [10],
            [20],
            [30],
        ];
    }

    /**
     * @dataProvider getSemaphoreSizes
     *
     * @param int $count Number of locks to test.
     */
    public function testAcquireFromMultipleSizeSemaphores(int $count) {
        $this->assertRunTimeGreaterThan(function () use ($count) {
            $this->semaphore = $this->createSemaphore($count);

            Loop::run(function () use ($count) {
                foreach (\range(0, $count - 1) as $value) {
                    $this->semaphore->acquire()->onResolve(function ($exception, $lock) {
                        if ($exception) {
                            throw $exception;
                        }

                        Loop::delay(100, [$lock, "release"]);
                    });
                }

                $lock = yield $this->semaphore->acquire();
                Loop::delay(100, [$lock, "release"]);
            });
        }, 200);
    }

    public function testSimultaneousAcquire() {
        $this->assertRunTimeGreaterThan(function () {
            $this->semaphore = $this->createSemaphore(1);

            Loop::run(function () {
                $promise1 = $this->semaphore->acquire();
                $promise2 = $this->semaphore->acquire();

                Loop::delay(100, function () use ($promise1) {
                    (yield $promise1)->release();
                });

                (yield $promise2)->release();
            });
        }, 100);
    }
}
