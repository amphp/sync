<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\Sync\PosixSemaphore;
use Amp\Sync\Semaphore;

/**
 * @group posix
 * @requires extension sysvmsg
 */
class PosixSemaphoreTest extends AbstractSemaphoreTest {
    /**
     * @param $locks
     *
     * @return \Amp\Sync\PosixSemaphore
     */
    public function createSemaphore(int $locks): Semaphore {
        return new PosixSemaphore($locks);
    }

    public function tearDown() {
        if ($this->semaphore && !$this->semaphore->isFreed()) {
            $this->semaphore->free();
        }
    }

    public function testCloneIsNewSemaphore() {
        Loop::run(function () {
            $this->semaphore = $this->createSemaphore(1);
            $clone = clone $this->semaphore;

            $lock = yield $clone->acquire();

            $this->assertSame(1, $this->semaphore->getAvailable());
            $this->assertSame(0, $clone->getAvailable());

            $lock->release();

            $clone->free();
        });
    }

    public function testFree() {
        $this->semaphore = $this->createSemaphore(1);

        $this->assertFalse($this->semaphore->isFreed());

        $this->semaphore->free();

        $this->assertTrue($this->semaphore->isFreed());
    }

    public function testSerializedIsSameSemaphore() {
        Loop::run(function () {
            $this->semaphore = $this->createSemaphore(1);
            $unserialized = unserialize(serialize($this->semaphore));

            $lock = yield $unserialized->acquire();

            $this->assertSame(0, $this->semaphore->getAvailable());
            $this->assertSame(0, $unserialized->getAvailable());

            $lock->release();
        });
    }
}
