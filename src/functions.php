<?php

namespace Amp\Sync;

use Amp\Pipeline\Emitter;

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
 * @return array{PipelineChannel<TReceive, TSend>, PipelineChannel<TSend, TReceive>}
 */
function createChannelPair(): array
{
    $west = new Emitter();
    $east = new Emitter();

    return [
        new PipelineChannel($west->pipe(), $east),
        new PipelineChannel($east->pipe(), $west),
    ];
}
