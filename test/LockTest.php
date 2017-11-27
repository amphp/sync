<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\TestCase;
use Amp\Sync\Lock;

class LockTest extends TestCase {
    public function testIsReleased() {
        $lock = new Lock($this->createCallback(1));
        $this->assertFalse($lock->isReleased());
        $lock->release();
        $this->assertTrue($lock->isReleased());
    }

    public function testIsReleasedOnDestruct() {
        $lock = new Lock($this->createCallback(1));
        unset($lock);
    }

    /**
     * @expectedException \Amp\Sync\LockAlreadyReleasedError
     */
    public function testThrowsOnMultiRelease() {
        $lock = new Lock($this->createCallback(1));
        $lock->release();
        $lock->release();
    }
}
