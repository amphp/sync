<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\Semaphore;
use function Amp\coroutine;
use function Amp\delay;
use function Revolt\launch;

abstract class AbstractSemaphoreTest extends AsyncTestCase
{
    protected ?Semaphore $semaphore;

    /**
     * @param int $locks Number of locks in the semaphore.
     *
     * @return Semaphore
     */
    abstract public function createSemaphore(int $locks): Semaphore;

    public function tearDown(): void
    {
        parent::tearDown();
        $this->semaphore = null; // Force Semaphore::__destruct() to be invoked.
    }

    public function testConstructorOnInvalidMaxLocks(): void
    {
        $this->expectException(\Error::class);

        $this->semaphore = $this->createSemaphore(-1);
    }

    public function testAcquire(): void
    {
        $this->semaphore = $this->createSemaphore(1);

        $lock = $this->semaphore->acquire();

        self::assertFalse($lock->isReleased());

        $lock->release();

        self::assertTrue($lock->isReleased());
    }

    public function testAcquireMultipleFromSingleLockSemaphore(): void
    {
        $this->setMinimumRuntime(0.3);

        $this->semaphore = $this->createSemaphore(1);

        $lock1 = $this->semaphore->acquire();
        self::assertSame(0, $lock1->getId());
        launch(function () use ($lock1): void {
            delay(0.1);
            $lock1->release();
        });

        $lock2 = $this->semaphore->acquire();
        self::assertSame(0, $lock2->getId());
        launch(function () use ($lock2): void {
            delay(0.1);
            $lock2->release();
        });

        $lock3 = $this->semaphore->acquire();
        self::assertSame(0, $lock3->getId());
        delay(0.1);
        $lock3->release();
    }

    public function testAcquireMultipleFromMultipleLockSemaphore(): void
    {
        $this->setMinimumRuntime(0.2);

        $this->semaphore = $this->createSemaphore(3);

        $lock1 = $this->semaphore->acquire();
        launch(function () use ($lock1): void {
            delay(0.1);
            $lock1->release();
        });

        $lock2 = $this->semaphore->acquire();
        self::assertNotSame($lock1->getId(), $lock2->getId());
        launch(function () use ($lock2): void {
            delay(0.101);
            $lock2->release();
        });

        $lock3 = $this->semaphore->acquire();
        self::assertNotSame($lock1->getId(), $lock3->getId());
        self::assertNotSame($lock2->getId(), $lock3->getId());
        launch(function () use ($lock3): void {
            delay(0.101);
            $lock3->release();
        });

        $lock4 = $this->semaphore->acquire();
        self::assertSame($lock1->getId(), $lock4->getId());
        delay(0.1);
        $lock4->release();
    }

    public function getSemaphoreSizes(): array
    {
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
    public function testAcquireFromMultipleSizeSemaphores(int $count): void
    {
        $this->setMinimumRuntime(0.1);

        $this->semaphore = $this->createSemaphore($count);

        foreach (\range(0, $count - 1) as $value) {
            launch(function (): void {
                $lock = $this->semaphore->acquire();
                delay(0.1);
                $lock->release();
            });
        }

        $lock = $this->semaphore->acquire();
        delay(0.1);
        $lock->release();
    }

    public function testSimultaneousAcquire(): void
    {
        $this->setMinimumRuntime(0.1);

        $this->semaphore = $this->createSemaphore(1);

        $promise1 = coroutine(fn() => $this->semaphore->acquire());
        $promise2 = coroutine(fn() => $this->semaphore->acquire());

        launch(function () use ($promise1): void {
            delay(0.1);
            $promise1->await()->release();
        });

        $promise2->await()->release();
    }
}
