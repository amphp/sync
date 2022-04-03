<?php

namespace Amp\Sync;

use Revolt\EventLoop;
use Revolt\EventLoop\Suspension;

final class LocalSemaphore implements Semaphore
{
    private int $locks = 0;

    /** @var \SplQueue<Suspension> */
    private readonly \SplQueue $waiting;

    /**
     * @param positive-int $maxLocks
     */
    public function __construct(private readonly int $maxLocks)
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if ($maxLocks < 1) {
            throw new \Error('The number of locks must be greater than 0, got ' . $maxLocks);
        }

        $this->waiting = new \SplQueue();
    }

    public function acquire(): Lock
    {
        if ($this->locks < $this->maxLocks) {
            ++$this->locks;
            return $this->createLock();
        }

        $this->waiting->enqueue($suspension = EventLoop::getSuspension());

        return $suspension->suspend();
    }

    private function release(): void
    {
        if (!$this->waiting->isEmpty()) {
            $suspension = $this->waiting->dequeue();
            $suspension->resume($this->createLock());

            return;
        }

        --$this->locks;
    }

    private function createLock(): Lock
    {
        return new Lock($this->release(...));
    }
}
