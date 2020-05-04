<?php

namespace Amp\Sync;

use Amp\Iterator;
use Amp\Producer;
use Amp\Promise;
use function Amp\call;

/**
 * Invokes the given callback while maintaining a lock from the provided mutex. The lock is automatically released after
 * invoking the callback or once the promise returned by the callback is resolved. If the callback returns a Generator,
 * it will be run as a coroutine. See Amp\call().
 *
 * @param Mutex    $mutex
 * @param callable $callback
 * @param array    ...$args
 *
 * @return Promise Resolves with the return value of the callback.
 */
function synchronized(Mutex $mutex, callable $callback, ...$args): Promise
{
    return call(static function () use ($mutex, $callback, $args): \Generator {
        /** @var Lock $lock */
        $lock = yield $mutex->acquire();

        try {
            return yield call($callback, ...$args);
        } finally {
            $lock->release();
        }
    });
}

/**
 * @param Iterator  $iterator
 * @param Semaphore $semaphore
 * @param callable  $processor
 *
 * @return Iterator
 */
function concurrentMap(Iterator $iterator, Semaphore $semaphore, callable $processor): Iterator
{
    return new Producer(function () use ($iterator, $semaphore, $processor) {
        /** @var \Throwable|null $error */
        $error = null;
        $pending = [];

        while (yield $iterator->advance()) {
            if ($error) {
                throw $error;
            }

            /** @var Lock $lock */
            $lock = yield $semaphore->acquire();

            $currentElement = $iterator->getCurrent();

            $promise = call(static function () use ($lock, $currentElement, $processor, &$error) {
                try {
                    yield call($processor, $currentElement);
                } catch (\Throwable $e) {
                    $error = $e;
                } finally {
                    $lock->release();
                }
            });

            $promiseId = \spl_object_id($promise);

            $pending[$promiseId] = $promise;
            $promise->onResolve(static function () use (&$pending, $promiseId) {
                unset($pending[$promiseId]);
            });
        }

        yield Promise\any($pending);

        if ($error) {
            throw $error;
        }
    });
}