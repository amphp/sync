<?php

namespace Amp\Sync\Tests;

use Amp\Sync\LocalMutex;
use Amp\Sync\Mutex;
use Amp\Sync\Test\AbstractMutexTest;

class LocalMutexTest extends AbstractMutexTest
{
    public function createMutex(): Mutex
    {
        return new LocalMutex;
    }
}
