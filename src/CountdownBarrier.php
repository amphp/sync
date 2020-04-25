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
    private $currentCount;
    /** @var Deferred */
    private $deferred;

    public function __construct(int $initialCount)
    {
        if ($initialCount < 1) {
            throw new Error('Counter must be positive');
        }

        $this->initialCount = $initialCount;
        $this->currentCount = $initialCount;
        $this->deferred = new Deferred();
    }

    public function getCurrentCount(): int
    {
        return $this->currentCount;
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

        if (0 === $this->currentCount) {
            throw new Error('CountdownBarrier already resolved');
        }

        if ($signalCount > $this->currentCount) {
            throw new Error('Signal count cannot be greater than current count');
        }

        $this->currentCount -= $signalCount;

        if (0 === $this->currentCount) {
            $this->deferred->resolve();
        }
    }

    /** @throws Error */
    public function addCount(int $signalCount = 1): void
    {
        if ($signalCount < 1) {
            throw new Error('Signal count must be greater or equals 1');
        }

        if (0 === $this->currentCount) {
            throw new Error('CountdownBarrier already resolved');
        }

        $this->currentCount += $signalCount;
    }

    public function promise(): Promise
    {
        return $this->deferred->promise();
    }
}
