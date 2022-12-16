<?php declare(strict_types=1);

namespace Amp\Sync;

class LocalSemaphoreTest extends AbstractSemaphoreTest
{
    public function createSemaphore(int $locks): Semaphore
    {
        return new LocalSemaphore($locks);
    }
}
