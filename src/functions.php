<?php

namespace Amp\Sync;

use Amp\CancelledException;
use Amp\Iterator;
use Amp\Producer;
use Amp\Promise;
use function Amp\call;
use function Amp\coroutine;

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
 * Concurrently map all iterator values using {@code $processor}.
 *
 * The order of the items in the resulting iterator is not guaranteed in any way.
 *
 * @param Iterator  $iterator Values to map.
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@code LocalSemaphore}
 * @param callable  $processor Processing callable, which is run as coroutine. It should not throw any errors,
 *     otherwise the entire operation is aborted.
 *
 * @return Iterator Mapped values.
 */
function concurrentMap(Iterator $iterator, Semaphore $semaphore, callable $processor): Iterator
{
    return new Producer(static function (callable $emit) use ($iterator, $semaphore, $processor) {
        $processor = coroutine($processor);

        /** @var \Throwable|null $error */
        $error = null;
        $pending = [];
        $locks = [];
        $gc = false;

        while (yield $iterator->advance()) {
            if ($error) {
                break;
            }

            /** @var Lock $lock */
            $lock = yield $semaphore->acquire();
            if ($gc || isset($locks[$lock->getId()])) {
                throw new CancelledException; // producer and locks have been GCed
            }

            $locks[$lock->getId()] = true;

            $currentElement = $iterator->getCurrent();

            $promise = call(static function () use (
                $lock,
                $currentElement,
                $processor,
                $emit,
                &$locks,
                &$error,
                &$gc
            ) {
                $done = false;

                try {
                    yield $emit(yield $processor($currentElement));

                    $done = true;
                } catch (\Throwable $e) {
                    if ($error === null) {
                        $error = $e;
                    }

                    $done = true;
                } finally {
                    unset($locks[$lock->getId()]);

                    if (!$done) {
                        $gc = true;
                    }

                    $lock->release();
                }
            });

            unset($lock);

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
