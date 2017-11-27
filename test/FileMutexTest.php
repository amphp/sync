<?php

namespace Amp\Sync\Test;

use Amp\Sync\FileMutex;
use Amp\Sync\Mutex;

class FileMutexTest extends AbstractMutexTest {
    public function createMutex(): Mutex {
        return new FileMutex;
    }
}
