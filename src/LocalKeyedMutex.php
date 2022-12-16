<?php declare(strict_types=1);

namespace Amp\Sync;

final class LocalKeyedMutex implements KeyedMutex
{
    private readonly LocalKeyedSemaphore $semaphore;

    public function __construct()
    {
        $this->semaphore = new LocalKeyedSemaphore(1);
    }

    public function acquire(string $key): Lock
    {
        return $this->semaphore->acquire($key);
    }
}
