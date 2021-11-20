<?php

namespace Amp\Sync\Test;

use Amp\Sync\PosixSemaphore;
use Amp\Sync\Semaphore;
use Amp\Sync\SyncException;
use function Amp\delay;
use function Amp\launch;

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
     * @return PosixSemaphore
     */
    public function createSemaphore(int $locks): Semaphore
    {
        return PosixSemaphore::create(self::ID . \bin2hex(\random_bytes(4)), $locks);
    }

    public function testConstructorOnInvalidMaxLocks(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Number of locks must be greater than 0");

        $this->semaphore = $this->createSemaphore(-1);
    }

    public function testCreateOnInvalidMaxLocks(): void
    {
        $this->expectException(\Error::class);

        PosixSemaphore::create(self::ID, -1);
    }

    public function testGetPermissions(): void
    {
        $id = self::ID . \bin2hex(\random_bytes(4));
        $this->semaphore = PosixSemaphore::create($id, 1);
        $used = PosixSemaphore::use($id);
        $used->setPermissions(0644);

        self::assertSame(0644, $this->semaphore->getPermissions());
    }

    public function testGetId(): void
    {
        $this->semaphore = $this->createSemaphore(1);

        self::assertStringStartsWith(self::ID, $this->semaphore->getId());
    }

    public function testUseOnInvalidSemaphoreId(): void
    {
        $this->expectException(SyncException::class);
        $this->expectExceptionMessage("No semaphore with that ID found");

        PosixSemaphore::use(1);
    }

    public function testCreateOnDuplicatedSemaphoreId(): void
    {
        $this->expectException(SyncException::class);
        $this->expectExceptionMessage("A semaphore with that ID already exists");

        $semaphore = PosixSemaphore::create(self::ID, 1);
        $semaphore::create(self::ID, 1);
    }

    public function testUse(): void
    {
        $this->setMinimumRuntime(0.1);

        $this->semaphore = $this->createSemaphore(1);

        $used = PosixSemaphore::use(self::ID);

        $future1 = launch(fn () => $used->acquire());
        $future2 = launch(fn () => $this->semaphore->acquire());

        $future3 = launch(function () use ($future1): void {
            delay(0.1);
            $future1->await()->release();
        });

        $future2->await()->release();
        $future3->await();
    }
}
