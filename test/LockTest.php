<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\TestCase;
use Amp\Sync\Lock;

class LockTest extends TestCase
{
    public function testIsReleased()
    {
        $lock = new Lock(0, $this->createCallback(1));
        $this->assertFalse($lock->isReleased());
        $lock->release();
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
        $lock->release();
        $this->assertTrue($lock->isReleased());
        $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testGetId()
    {
        $id = 42;
        $lock = new Lock($id, $this->createCallback(1));
        $this->assertSame($id, $lock->getId());
        $lock->release();
    }
}
