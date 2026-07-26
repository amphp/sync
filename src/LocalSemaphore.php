<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Revolt\EventLoop;
use Revolt\EventLoop\Suspension;

final class LocalSemaphore implements Semaphore
{
    use ForbidCloning;
    use ForbidSerialization;

    private int $locks = 0;

    /** @var array<int, Suspension> */
    private array $waiting = [];

    /**
     * @param positive-int $maxLocks
     */
    public function __construct(private readonly int $maxLocks)
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if ($maxLocks < 1) {
            throw new \ValueError('The number of locks must be greater than 0, got ' . $maxLocks);
        }
    }

    #[\Override]
    public function acquire(?Cancellation $cancellation = null): Lock
    {
        if ($this->locks < $this->maxLocks) {
            ++$this->locks;
            return $this->createLock();
        }

        $suspension = EventLoop::getSuspension();
        $key = \spl_object_id($suspension);
        $this->waiting[$key] = $suspension;

        $id = $cancellation?->subscribe(function (CancelledException $exception) use ($key, $suspension): void {
            unset($this->waiting[$key]);
            $suspension->throw($exception);
        });

        try {
            return $suspension->suspend();
        } finally {
            /** @psalm-suppress PossiblyNullArgument $id will not be null if $cancellation is not null. */
            $cancellation?->unsubscribe($id);
        }
    }

    private function release(): void
    {
        $key = \array_key_first($this->waiting);

        if ($key !== null) {
            $suspension = $this->waiting[$key];
            unset($this->waiting[$key]);
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
