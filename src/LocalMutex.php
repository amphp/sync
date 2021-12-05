<?php

namespace Amp\Sync;

use Amp\DeferredFuture;

final class LocalMutex implements Mutex
{
    private bool $locked = false;

    /** @var DeferredFuture[] */
    private array $waitingDeferreds = [];

    public function acquire(): Lock
    {
        if (!$this->locked) {
            $this->locked = true;

            return $this->createLock();
        }

        $this->waitingDeferreds[] = $waitingDeferred = new DeferredFuture;

        return $waitingDeferred->getFuture()->await();
    }

    private function release(): void
    {
        if (!empty($this->waitingDeferreds)) {
            $waitingDeferred = \array_shift($this->waitingDeferreds);
            $waitingDeferred->complete($this->createLock());

            return;
        }

        $this->locked = false;
    }

    private function createLock(): Lock
    {
        return new Lock(0, \Closure::fromCallable([$this, 'release']));
    }
}
