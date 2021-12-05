<?php

namespace Amp\Sync;

class LocalSemaphoreTest extends AbstractSemaphoreTest
{
    public function createSemaphore(int $locks): Semaphore
    {
        return new LocalSemaphore($locks);
    }
}
