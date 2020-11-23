<?php

namespace Amp\Sync;

final class LocalKeyedMutex implements KeyedMutex
{
    /** @var LocalMutex[] */
    private array $mutex = [];

    /** @var int[] */
    private array $locks = [];

    public function acquire(string $key): Lock
    {
        if (!isset($this->mutex[$key])) {
            $this->mutex[$key] = new LocalMutex;
            $this->locks[$key] = 0;
        }

        $this->locks[$key]++;

        $lock = $this->mutex[$key]->acquire();

        return new Lock(0, function () use ($lock, $key): void {
            if (--$this->locks[$key] === 0) {
                unset($this->mutex[$key], $this->locks[$key]);
            }

            $lock->release();
        });
    }
}
