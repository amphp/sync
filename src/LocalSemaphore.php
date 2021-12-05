<?php

namespace Amp\Sync;

use Amp\DeferredFuture;

final class LocalSemaphore implements Semaphore
{
    /** @var int[] */
    private array $locks;

    /** @var DeferredFuture[] */
    private array $waitingDeferreds = [];

    public function __construct(int $maxLocks)
    {
        if ($maxLocks < 1) {
            throw new \Error('The number of locks must be greater than 0, got ' . $maxLocks);
        }

        $this->locks = \range(0, $maxLocks - 1);
    }

    public function acquire(): Lock
    {
        if (!empty($this->locks)) {
            return $this->createLock(\array_pop($this->locks));
        }

        $this->waitingDeferreds[] = $deferred = new DeferredFuture;

        return $deferred->getFuture()->await();
    }

    private function release(int $id): void
    {
        if (!empty($this->waitingDeferreds)) {
            $deferred = \array_shift($this->waitingDeferreds);
            $deferred->complete($this->createLock($id));

            return;
        }

        $this->locks[] = $id;
    }

    private function createLock(int $id): Lock
    {
        return new Lock(fn () => $this->release($id));
    }
}
