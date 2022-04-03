<?php

namespace Amp\Sync;

use Revolt\EventLoop;
use Revolt\EventLoop\Suspension;

final class LocalMutex implements Mutex
{
    private bool $locked = false;

    /** @var \SplQueue<Suspension> */
    private readonly \SplQueue $waiting;

    public function __construct()
    {
        $this->waiting = new \SplQueue();
    }

    public function acquire(): Lock
    {
        if (!$this->locked) {
            $this->locked = true;

            return $this->createLock();
        }

        $this->waiting->enqueue($suspension = EventLoop::getSuspension());

        return $suspension->suspend();
    }

    private function release(): void
    {
        if (!$this->waiting->isEmpty()) {
            $waiting = $this->waiting->dequeue();
            $waiting->resume($this->createLock());

            return;
        }

        $this->locked = false;
    }

    private function createLock(): Lock
    {
        return new Lock($this->release(...));
    }
}
