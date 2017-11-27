<?php

namespace Amp\Sync\Test;

use Amp\Sync\Semaphore;
use Amp\Sync\ThreadedSemaphore;

/**
 * @requires extension pthreads
 */
class ThreadedSemaphoreTest extends AbstractSemaphoreTest {
    public function createSemaphore(int $locks): Semaphore {
        return new ThreadedSemaphore($locks);
    }
}
