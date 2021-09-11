<?php

namespace Amp\Sync;

use Amp\Deferred;

class LocalSemaphore implements Semaphore
{
    /** @var int[] */
    private array $locks;

    /** @var Deferred[] */
    private array $queue = [];

    public function __construct(int $maxLocks)
    {
        if ($maxLocks < 1) {
            throw new \Error('The number of locks must be greater than 0');
        }

        $this->locks = \range(0, $maxLocks - 1);
    }

    /** {@inheritdoc} */
    public function acquire(): Lock
    {
        if (!empty($this->locks)) {
            return new Lock(\array_shift($this->locks), \Closure::fromCallable([$this, 'release']));
        }

        $this->queue[] = $deferred = new Deferred;
        return $deferred->getFuture()->join();
    }

    private function release(Lock $lock): void
    {
        $id = $lock->getId();

        if (!empty($this->queue)) {
            $deferred = \array_shift($this->queue);
            $deferred->complete(new Lock($id, \Closure::fromCallable([$this, 'release'])));
            return;
        }

        $this->locks[] = $id;
    }
}
