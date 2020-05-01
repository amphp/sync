<?php

namespace Amp\Sync\Tests;

use Amp\Sync\LocalSemaphore;
use Amp\Sync\Mutex;
use Amp\Sync\SemaphoreMutex;
use Amp\Sync\Test\AbstractMutexTest;

class SemaphoreMutexTest extends AbstractMutexTest
{
    const ID = __CLASS__;

    public function createMutex(): Mutex
    {
        return new SemaphoreMutex(new LocalSemaphore(1));
    }

    public function testSemaphoreWithMultipleLocks(): \Generator
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot use a semaphore with more than a single lock');

        $mutex = new SemaphoreMutex(new LocalSemaphore(2));
        while (yield $mutex->acquire());
    }
}
