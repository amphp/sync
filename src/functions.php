<?php

namespace Amp\Sync;

/**
 * Invokes the given Closure while maintaining a lock from the provided mutex.
 *
 * The lock is automatically released after the Closure returns.
 *
 * @template T
 *
 * @param Mutex $mutex
 * @param \Closure(...$args):T $callback
 * @param mixed ...$args
 *
 * @return T The return value of the callback.
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
