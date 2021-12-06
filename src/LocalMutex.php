<?php

namespace Amp\Sync;

use Revolt\EventLoop;
use Revolt\EventLoop\Suspension;

final class LocalMutex implements Mutex
{
    private bool $locked = false;

    /** @var Suspension[] */
    private array $waiting = [];

    public function acquire(): Lock
    {
        if (!$this->locked) {
            $this->locked = true;

            return $this->createLock();
        }

        $this->waiting[] = $suspension = EventLoop::createSuspension();

        return $suspension->suspend();
    }

    private function release(): void
    {
        if (!empty($this->waiting)) {
            $waiting = \array_shift($this->waiting);
            $waiting->resume($this->createLock());

            return;
        }

        $this->locked = false;
    }

    private function createLock(): Lock
    {
        return new Lock(\Closure::fromCallable([$this, 'release']));
    }
}
