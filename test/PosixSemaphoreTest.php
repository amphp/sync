<?php

namespace Amp\Sync;

use function Amp\async;
use function Amp\delay;

/**
 * @group posix
 * @requires extension sysvmsg
 */
class PosixSemaphoreTest extends AbstractSemaphoreTest
{
    private const ID = __CLASS__;

    public function makeId(): string
    {
        return \spl_object_hash($this);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!\extension_loaded('sysvmsg')) {
            self::markTestSkipped('ext-sysvmsg missing');
        }

        // Remove queue if it still exists
        \msg_remove_queue(\msg_get_queue(\abs(\unpack("l", \md5(self::ID, true))[1])));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Await __destruct freeing semaphore
        delay(1);
    }

    /**
     * @param int $locks
     *
     * @return PosixSemaphore
     */
    public function createSemaphore(int $locks): Semaphore
    {
        return PosixSemaphore::create($locks);
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

        PosixSemaphore::create(-1);
    }

    public function testGetPermissions(): void
    {
        $this->semaphore = PosixSemaphore::create(1);
        $used = PosixSemaphore::use($this->semaphore->getId());
        $used->setPermissions(0644);

        self::assertSame(0644, $this->semaphore->getPermissions());
    }

    public function testUseOnInvalidSemaphoreId(): void
    {
        $this->expectException(SyncException::class);
        $this->expectExceptionMessage("No semaphore with that ID found");

        PosixSemaphore::use(1);
    }

    public function testUse(): void
    {
        $this->setMinimumRuntime(0.1);

        $this->semaphore = $this->createSemaphore(1);

        $used = PosixSemaphore::use($this->semaphore->getId());

        $future1 = async(fn () => $used->acquire());
        $future2 = async(fn () => $this->semaphore->acquire());

        $future3 = async(function () use ($future1): void {
            delay(0.1);
            $future1->await()->release();
        });

        $future2->await()->release();
        $future3->await();
    }
}
