<?php

namespace Amp\Sync\Test;

use Amp\Sync\PosixSemaphore;
use Amp\Sync\Semaphore;
use Amp\Sync\SyncException;
use function Amp\async;
use function Amp\await;
use function Amp\defer;
use function Amp\delay;

/**
 * @group posix
 * @requires extension sysvmsg
 */
class PosixSemaphoreTest extends AbstractSemaphoreTest
{
    const ID = __CLASS__ . '/4';

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
        $this->semaphore = PosixSemaphore::create(self::ID, 1);
        $used = PosixSemaphore::use(self::ID);
        $used->setPermissions(0644);

        $this->assertSame(0644, $this->semaphore->getPermissions());
    }

    public function testGetId(): void
    {
        $this->semaphore = $this->createSemaphore(1);

        $this->assertSame(self::ID, $this->semaphore->getId());
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

    public function testUse()
    {
        $this->setMinimumRuntime(100);

        $this->semaphore = $this->createSemaphore(1);

        $used = PosixSemaphore::use(self::ID);

        $promise1 = async(fn() => $used->acquire());
        $promise2 = async(fn() => $this->semaphore->acquire());

        defer(function () use ($promise1): void {
            delay(100);
            await($promise1)->release();
        });

        await($promise2)->release();
    }
}
