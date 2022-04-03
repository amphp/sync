<?php

namespace Amp\Sync;

use Amp\Pipeline\Queue;
use Amp\Sync\Internal\ConcurrentIteratorChannel;

/**
 * Invokes the given Closure while maintaining a lock from the provided mutex.
 *
 * The lock is automatically released after the Closure returns.
 *
 * @template T
 *
 * @param \Closure(...):T $synchronized
 *
 * @return T The return value of the Closure.
 */
function synchronized(Mutex $mutex, \Closure $synchronized, mixed ...$args): mixed
{
    $lock = $mutex->acquire();

    try {
        return $synchronized(...$args);
    } finally {
        $lock->release();
    }
}

/**
 * @return array{ConcurrentIteratorChannel, ConcurrentIteratorChannel}
 */
function createChannelPair(): array
{
    $west = new Queue();
    $east = new Queue();

    return [
        new ConcurrentIteratorChannel($west->iterate(), $east),
        new ConcurrentIteratorChannel($east->iterate(), $west),
    ];
}
