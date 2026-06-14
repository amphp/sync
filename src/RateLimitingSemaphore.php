<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Revolt\EventLoop;

/**
 * When a locked is released from this semaphore, it does not become available to be acquired again until
 * the given lock-period has elapsed. This is useful when a number of operations or requests must be
 * limited to a particular quantity within a certain time period.
 */
final class RateLimitingSemaphore implements Semaphore
{
    use ForbidCloning;
    use ForbidSerialization;

    /** @var array<string, string> Array of event-loop delay callback IDs. */
    private array $timers = [];

    private int $waitingCount = 0;

    /**
     * @param float $lockPeriod Time after which a lock is released from the semaphore after being initially
     * released by the consumer.
     */
    public function __construct(
        private readonly Semaphore $semaphore,
        private readonly float $lockPeriod,
    ) {
        if ($lockPeriod <= 0) {
            throw new \ValueError('The lock period must be greater than 0, got ' . (string) $lockPeriod);
        }
    }

    #[\Override]
    public function acquire(): Lock
    {
        if ($this->waitingCount++ === 0) {
            foreach ($this->timers as $callbackId) {
                EventLoop::reference($callbackId);
            }
        }

        try {
            $lock = $this->semaphore->acquire();
        } finally {
            if (--$this->waitingCount === 0) {
                foreach ($this->timers as $callbackId) {
                    EventLoop::unreference($callbackId);
                }
            }
        }

        return new Lock(fn () => $this->release($lock));
    }

    private function release(Lock $lock): void
    {
        $callbackId = EventLoop::delay(
            $this->lockPeriod,
            function (string $callbackId) use ($lock): void {
                unset($this->timers[$callbackId]);
                $lock->release();
            },
        );

        if ($this->waitingCount === 0) {
            EventLoop::unreference($callbackId);
        }

        $this->timers[$callbackId] = $callbackId;
    }
}
