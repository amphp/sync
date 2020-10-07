<?php

namespace Amp\Sync;

class SemaphoreMutex implements Mutex
{
    /** @var Semaphore */
    private Semaphore $semaphore;

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
        if ($lock->getId() !== 0) {
            $lock->release();
            throw new \Error("Cannot use a semaphore with more than a single lock");
        }
        return $lock;
    }
}
