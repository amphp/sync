<?php

namespace Amp\Sync;

use Amp\CancelledException;
use Amp\Iterator;
use Amp\Producer;
use Amp\Promise;
use function Amp\asyncCall;
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
 * Concurrently act on iterator values using {@code $processor}.
 *
 * @param Iterator  $iterator Values to process.
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@code LocalSemaphore}
 * @param callable  $processor Processing callable, which is run as coroutine. It should not throw any errors,
 *     otherwise the entire operation is aborted.
 *
 * @return Iterator Result values.
 */
function concurrent(Iterator $iterator, Semaphore $semaphore, callable $processor): Iterator
{
    return new Producer(static function (callable $emit) use ($iterator, $semaphore, $processor) {
        // one dummy item, because we can't start the barrier with a count of zero
        $barrier = new CountingBarrier(1);

        /** @var \Throwable|null $error */
        $error = null;
        $locks = [];
        $gc = false;

        $processor = coroutine($processor);
        $processor = static function (Lock $lock, $currentElement) use (
            $processor,
            $emit,
            $barrier,
            &$locks,
            &$error,
            &$gc
        ) {
            $done = false;

            try {
                yield $processor($currentElement, $emit);

                $done = true;
            } catch (\Throwable $e) {
                $error = $error ?? $e;
                $done = true;
            } finally {
                if (!$done) {
                    $gc = true;
                }

                unset($locks[$lock->getId()]);

                $lock->release();
                $barrier->decrease();
            }
        };

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
            $barrier->increase();

            asyncCall($processor, $lock, $iterator->getCurrent());
        }

        $barrier->decrease(); // remove dummy item
        yield $barrier->await();

        if ($error) {
            throw $error;
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
    $processor = coroutine($processor);

    return concurrent($iterator, $semaphore, coroutine(static function ($value, callable $emit) use ($processor) {
        $value = yield $processor($value);

        yield $emit($value);
    }));
}

/**
 * Concurrently filter all iterator values using {@code $filter}.
 *
 * The order of the items in the resulting iterator is not guaranteed in any way.
 *
 * @param Iterator  $iterator Values to map.
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@code LocalSemaphore}
 * @param callable  $filter Processing callable, which is run as coroutine. It should not throw any errors,
 *     otherwise the entire operation is aborted. Must resolve to a boolean, true to keep values in the resulting
 *     iterator.
 *
 * @return Iterator Values, where {@code $filter} resolved to {@code true}.
 */
function concurrentFilter(Iterator $iterator, Semaphore $semaphore, callable $filter): Iterator
{
    $filter = coroutine($filter);

    return concurrent($iterator, $semaphore, coroutine(static function ($value, callable $emit) use ($filter) {
        if (yield $filter($value)) {
            yield $emit($value);
        }
    }));
}

/**
 * Concurrently invoke a callback on all iterator values using {@code $processor}.
 *
 * @param Iterator  $iterator Values to act on.
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@code LocalSemaphore}
 * @param callable  $processor Processing callable, which is run as coroutine. It should not throw any errors,
 *     otherwise the entire operation is aborted.
 *
 * @return Promise
 */
function concurrentForeach(Iterator $iterator, Semaphore $semaphore, callable $processor): Promise
{
    $processor = coroutine($processor);

    return call(static function () use ($iterator, $semaphore, $processor) {
        yield Iterator\toArray(concurrent($iterator, $semaphore, coroutine(static function ($value) use ($processor) {
            yield $processor($value);
        })));
    });
}
