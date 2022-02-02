<?php

namespace Amp\Sync;

use Amp\Pipeline\Queue;

/**
 * Invokes the given Closure while maintaining a lock from the provided mutex.
 *
 * The lock is automatically released after the Closure returns.
 *
 * @template T
 *
 * @param Semaphore $semaphore
 * @param \Closure(...mixed):T $synchronized
 * @param mixed ...$args
 *
 * @return T The return value of the Closure.
 */
function synchronized(Semaphore $semaphore, \Closure $synchronized, mixed ...$args): mixed
{
    $lock = $semaphore->acquire();

    try {
        return $synchronized(...$args);
    } finally {
        $lock->release();
    }
}

/**
 * @template TReceive
 * @template TSend
 *
 * @return array{ConcurrentIteratorChannel<TReceive, TSend>, ConcurrentIteratorChannel<TSend, TReceive>}
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
