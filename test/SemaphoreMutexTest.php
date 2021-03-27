<?php

namespace Amp\Sync\Test;

use Amp\Sync\LocalSemaphore;
use Amp\Sync\Mutex;
use Amp\Sync\SemaphoreMutex;

class SemaphoreMutexTest extends AbstractMutexTest
{
    const ID = __CLASS__;

    public function createMutex(): Mutex
    {
        return new SemaphoreMutex(new LocalSemaphore(1));
    }

    public function testSemaphoreWithMultipleLocks(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot use a semaphore with more than a single lock');

        $mutex = new SemaphoreMutex(new LocalSemaphore(2));
        while ($mutex->acquire()) {
            ;
        }
    }
}
