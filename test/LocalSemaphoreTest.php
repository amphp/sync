<?php

namespace Amp\Sync\Tests;

use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;
use Amp\Sync\Test\AbstractSemaphoreTest;

class LocalSemaphoreTest extends AbstractSemaphoreTest
{
    public function createSemaphore(int $locks): Semaphore
    {
        return new LocalSemaphore($locks);
    }
}
