<?php

namespace Amp\Sync\Test;

use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;

class LocalSemaphoreTest extends AbstractSemaphoreTest {
    public function createSemaphore(int $locks): Semaphore {
        return new LocalSemaphore($locks);
    }
}
