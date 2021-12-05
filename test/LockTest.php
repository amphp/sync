<?php

namespace Amp\Sync;

use Amp\PHPUnit\AsyncTestCase;

class LockTest extends AsyncTestCase
{
    public function testIsReleased(): void
    {
        $lock = new Lock(0, $this->createCallback(1));
        self::assertFalse($lock->isReleased());
        $lock->release();
        self::assertTrue($lock->isReleased());
    }

    public function testIsReleasedOnDestruct(): void
    {
        $lock = new Lock(0, $this->createCallback(1));
        unset($lock);
    }

    public function testThrowsOnMultiRelease(): void
    {
        $lock = new Lock(0, $this->createCallback(1));
        $lock->release();
        self::assertTrue($lock->isReleased());
        $lock->release();
        self::assertTrue($lock->isReleased());
    }

    public function testGetId(): void
    {
        $id = 42;
        $lock = new Lock($id, $this->createCallback(1));
        self::assertSame($id, $lock->getId());
        $lock->release();
    }
}
