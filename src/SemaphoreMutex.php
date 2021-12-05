<?php

namespace Amp\Sync;

final class SemaphoreMutex implements Mutex
{
    private Semaphore $semaphore;

    private bool $locked = false;

    /**
     * @param Semaphore $semaphore A semaphore with a single lock.
     */
    public function __construct(Semaphore $semaphore)
    {
        $this->semaphore = $semaphore;
    }

    /** {@inheritdoc} */
    public function acquire(): Lock
    {
        $lock = $this->semaphore->acquire();

        if ($this->locked) {
            throw new \Error("Cannot use a semaphore with more than a single lock");
        }

        $this->locked = true;
        return new Lock(function () use ($lock): void {
            $this->locked = false;
            $lock->release();
        });
    }
}
