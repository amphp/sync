<?php

namespace Amp\Sync;

use Revolt\EventLoop;
use Revolt\EventLoop\Suspension;

final class LocalSemaphore implements Semaphore
{
    /** @var int[] */
    private array $locks;

    /** @var Suspension[] */
    private array $waiting = [];

    /**
     * @param positive-int $maxLocks
     */
    public function __construct(int $maxLocks)
    {
        /** @psalm-suppress TypeDoesNotContainType */
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

        $this->waiting[] = $suspension = EventLoop::getSuspension();

        return $suspension->suspend();
    }

    private function release(int $id): void
    {
        if (!empty($this->waiting)) {
            $deferred = \array_shift($this->waiting);
            $deferred->resume($this->createLock($id));

            return;
        }

        $this->locks[] = $id;
    }

    private function createLock(int $id): Lock
    {
        return new Lock(fn () => $this->release($id));
    }
}
