<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\Lock;

class LockTest extends AsyncTestCase
{
    public function testIsReleased()
    {
        $lock = new Lock(0, $this->createCallback(1));
        $this->assertFalse($lock->isReleased());
        yield $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testIsReleasedOnDestruct()
    {
        $lock = new Lock(0, $this->createCallback(1));
        unset($lock);
    }

    public function testThrowsOnMultiRelease()
    {
        $lock = new Lock(0, $this->createCallback(1));
        yield $lock->release();
        $this->assertTrue($lock->isReleased());
        yield $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testGetId()
    {
        $id = 42;
        $lock = new Lock($id, $this->createCallback(1));
        $this->assertSame($id, $lock->getId());
        yield $lock->release();
    }
}
