<?php

namespace Amp\Sync\Test;

use Amp\Sync\Mutex;
use Amp\Sync\ThreadedMutex;

/**
 * @requires extension pthreads
 */
class ThreadedMutexTest extends AbstractMutexTest {
    public function createMutex(): Mutex {
        return new ThreadedMutex;
    }
}
