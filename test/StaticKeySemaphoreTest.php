<?php declare(strict_types=1);

namespace Amp\Sync;

class StaticKeySemaphoreTest extends AbstractSemaphoreTest
{
    #[\Override]
    protected function createSemaphore(int $locks): Semaphore
    {
        return new StaticKeySemaphore(new LocalKeyedSemaphore($locks), 'key');
    }
}
