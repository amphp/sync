<?php

namespace Amp\Sync;

/**
 * Invokes the given callback while maintaining a lock from the provided mutex. The lock is automatically released after
 * invoking the callback or once the promise returned by the callback is resolved. If the callback returns a Generator,
 * it will be run as a coroutine. See Amp\call().
 *
 * @param Mutex    $mutex
 * @param callable $callback
 * @param array    ...$args
 *
 * @return mixed The return value of the callback.
 */
function synchronized(Mutex $mutex, callable $callback, ...$args): mixed
{
    $lock = $mutex->acquire();

    try {
        return $callback(...$args);
    } finally {
        $lock->release();
    }
}
