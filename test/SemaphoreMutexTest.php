<?php

namespace Amp\Sync\Test;

use Amp\Promise;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Mutex;
use Amp\Sync\SemaphoreMutex;

class SemaphoreMutexTest extends AbstractMutexTest {
    const ID = __CLASS__;

    public function createMutex(): Mutex {
        return new SemaphoreMutex(new LocalSemaphore(1));
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage Cannot use a semaphore with more than a single lock
     */
    public function testSemaphoreWithMultipleLocks() {
        $mutex = new SemaphoreMutex(new LocalSemaphore(2));
        while (Promise\wait($mutex->acquire()));
    }
}
