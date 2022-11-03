<?php

namespace Amp\Sync;

final class LocalMutex implements Mutex
{
    private readonly LocalSemaphore $semaphore;

    public function __construct()
    {
        $this->semaphore = new LocalSemaphore(1);
    }

    public function acquire(): Lock
    {
        return $this->semaphore->acquire();
    }
}
