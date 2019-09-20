<?php

namespace Amp\Sync\Test;

use Amp\Sync\LocalMutex;
use Amp\Sync\Mutex;

class LocalMutexTest extends AbstractMutexTest
{
    public function createMutex(): Mutex
    {
        return new LocalMutex;
    }
}
