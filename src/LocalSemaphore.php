<?php

namespace Amp\Sync;

use Amp\DeferredFuture;

final class LocalSemaphore implements Semaphore
{
    /** @var int[] */
    private array $locks;

    /** @var DeferredFuture[] */
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
            $id = \array_pop($this->locks);
            return $this->createLock($id);
        }

        $this->queue[] = $deferred = new DeferredFuture;
        return $deferred->getFuture()->await();
    }

    private function release(int $id): void
    {
        if (!empty($this->queue)) {
            $deferred = \array_shift($this->queue);
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
