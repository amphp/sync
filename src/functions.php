<?php

namespace Amp\Sync;

/**
 * Invokes the given Closure while maintaining a lock from the provided mutex.
 *
 * The lock is automatically released after the Closure returns.
 *
 * @template T
 *
 * @param Semaphore $semaphore
 * @param \Closure(...$args):T $synchronized
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
