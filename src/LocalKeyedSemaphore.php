<?php

namespace Amp\Sync;

final class LocalKeyedSemaphore implements KeyedSemaphore
{
    /** @var LocalSemaphore[] */
    private array $semaphore = [];

    /** @var int[] */
    private array $locks = [];

    private int $maxLocks;

    public function __construct(int $maxLocks)
    {
        $this->maxLocks = $maxLocks;
    }

    public function acquire(string $key): Lock
    {
        if (!isset($this->semaphore[$key])) {
            $this->semaphore[$key] = new LocalSemaphore($this->maxLocks);
            $this->locks[$key] = 0;
        }

        $this->locks[$key]++;

        $lock = $this->semaphore[$key]->acquire();

        return new Lock(0, function () use ($lock, $key): void {
            if (--$this->locks[$key] === 0) {
                unset($this->semaphore[$key], $this->locks[$key]);
            }

            $lock->release();
        });
    }
}
