<?php

namespace Amp\Sync;

use Amp\Deferred;
use Amp\Promise;
use Error;

/**
 * A countdown barrier returns a promise that is resolved when it was signaled n times.
 *
 * **Example**
 *
 * ```php
 * $countdownBarrier = new \Amp\CountdownBarrier(2);
 * $countdownBarrier->signal();
 * $countdownBarrier->signal(); // promise is now resolved
 * ```
 */
final class CountdownBarrier
{
    /** @var int */
    private $initialCount;
    /** @var int */
    private $remainingCount;
    /** @var Deferred */
    private $deferred;

    public function __construct(int $initialCount)
    {
        if ($initialCount < 1) {
            throw new Error('Counter must be positive');
        }

        $this->initialCount = $initialCount;
        $this->remainingCount = $initialCount;
        $this->deferred = new Deferred();
    }

    public function getRemainingCount(): int
    {
        return $this->remainingCount;
    }

    public function getInitialCount(): int
    {
        return $this->initialCount;
    }

    /** @throws Error */
    public function signal(int $signalCount = 1): void
    {
        if ($signalCount < 1) {
            throw new Error('Signal count must be greater or equals 1');
        }

        if (0 === $this->remainingCount) {
            throw new Error('CountdownBarrier already resolved');
        }

        if ($signalCount > $this->remainingCount) {
            throw new Error('Signal count cannot be greater than current count');
        }

        $this->remainingCount -= $signalCount;

        if ($this->remainingCount === 0) {
            $this->deferred->resolve();
        }
    }

    /** @throws Error */
    public function addCount(int $signalCount = 1): void
    {
        if ($signalCount < 1) {
            throw new Error('Signal count must be greater or equals 1');
        }

        if (0 === $this->remainingCount) {
            throw new Error('CountdownBarrier already resolved');
        }

        $this->remainingCount += $signalCount;
    }

    public function promise(): Promise
    {
        return $this->deferred->promise();
    }
}
