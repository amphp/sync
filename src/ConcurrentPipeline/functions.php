<?php

namespace Amp\Sync\ConcurrentPipeline;

use Amp\Iterator;
use Amp\Pipeline;
use Amp\PipelineSource;
use Amp\Promise;
use Amp\Sync\Barrier;
use Amp\Sync\Lock;
use Amp\Sync\Semaphore;
use function Amp\await;
use function Amp\defer;

/**
 * Concurrently act on source values using {@code $processor}.
 *
 * @param Pipeline  $source Values to process.
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@code LocalSemaphore}
 * @param callable  $processor Processing callable, which is run as coroutine. It should not throw any errors,
 *     otherwise the entire operation is aborted.
 *
 * @return Pipeline Result values.
 */
function transform(Pipeline $source, Semaphore $semaphore, callable $processor): Pipeline
{
    $pipeline = new PipelineSource;

    // one dummy item, because we can't start the barrier with a count of zero
    $barrier = new Barrier(1);

    /** @var \Throwable|null $error */
    $error = null;

    defer(static function () use ($source, $semaphore, $processor, $pipeline, $barrier, &$error) {
        $locks = [];
        $gc = false;

        $emit = \Closure::fromCallable([$pipeline, 'emit']);

        $processor = static function (Lock $lock, $currentElement) use (
            $processor,
            $emit,
            $barrier,
            &$locks,
            &$error,
            &$gc
        ): void {
            $done = false;

            try {
                $processorValue = $processor($currentElement, $emit);
                if ($processorValue instanceof Promise) {
                    await($processorValue);
                }

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
                $barrier->arrive();
            }
        };

        while (null !== $current = $source->continue()) {
            if ($error) {
                break;
            }

            $lock = $semaphore->acquire();
            if ($gc || isset($locks[$lock->getId()])) {
                // Throwing here causes a segfault on PHP 7.3
                return; // throw new CancelledException; // producer and locks have been GCed
            }

            $locks[$lock->getId()] = true;
            $barrier->register();

            defer($processor, $lock, $current);
        }

        $barrier->arrive(); // remove dummy item

        $barrier->await();

        if ($error) {
            $pipeline->fail($error);
        } else {
            $pipeline->complete();
        }
    });

    return $pipeline->pipe();
}

/**
 * Concurrently map all source values using {@code $processor}.
 *
 * The order of the items in the resulting source is not guaranteed in any way.
 *
 * @param Pipeline  $source Values to map.
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@code LocalSemaphore}
 * @param callable  $processor Processing callable, which is run as coroutine. It should not throw any errors,
 *     otherwise the entire operation is aborted.
 *
 * @return Pipeline Mapped values.
 */
function map(Pipeline $source, Semaphore $semaphore, callable $processor): Pipeline
{
    return transform($source, $semaphore, static function ($value, callable $emit) use ($processor) {
        $emit($processor($value));
    });
}

/**
 * Concurrently filter all source values using {@code $filter}.
 *
 * The order of the items in the resulting source is not guaranteed in any way.
 *
 * @param Iterator  $source Values to map.
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@code LocalSemaphore}
 * @param callable  $filter Processing callable, which is run as coroutine. It should not throw any errors,
 *     otherwise the entire operation is aborted. Must resolve to a boolean, true to keep values in the resulting
 *     source.
 *
 * @return Iterator Values, where {@code $filter} resolved to {@code true}.
 */
function filter(Pipeline $source, Semaphore $semaphore, callable $filter): Pipeline
{
    return transform($source, $semaphore, static function ($value, callable $emit) use ($filter) {
        $keep = $filter($value);
        if (!\is_bool($keep)) {
            throw new \TypeError(__NAMESPACE__ . '\filter\'s callable must resolve to a boolean value, got ' . \gettype($keep));
        }

        if ($keep) {
            $emit($value);
        }
    });
}

/**
 * Concurrently invoke a callback on all source values using {@code $processor}.
 *
 * @param Pipeline  $source Values to act on.
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@code LocalSemaphore}
 * @param callable  $processor Processing callable, which is run as coroutine. It should not throw any errors,
 *     otherwise the entire operation is aborted.
 *
 * @return int
 */
function each(Pipeline $source, Semaphore $semaphore, callable $processor): int
{
    return await(Pipeline\discard(transform(
        $source,
        $semaphore,
        static function ($value, callable $emit) use ($processor) {
            $processor($value);
            $emit(true);
        }
    )));
}
