<?php

namespace Amp\Sync\Pipeline;

use Amp\Pipeline\Pipeline;
use Amp\Pipeline\PipelineOperator;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;

/**
 * Concurrently act on a pipeline using the given set of operators. The resulting pipeline will *not* necessarily be
 * in the same order as the source pipeline, however, items are emitted as soon as they are available.
 *
 * @template TValue
 * @template TResult
 *
 * @param Semaphore $semaphore Semaphore limiting the concurrency, e.g. {@see LocalSemaphore}.
 * @param PipelineOperator ...$operators Set of operators to act upon each value emitted. See {@see Pipeline::pipe()}.
 *
 * @return PipelineOperator<TValue, TResult>
 */
function concurrent(Semaphore $semaphore, PipelineOperator ...$operators): PipelineOperator
{
    return new Internal\ConcurrentOperator($semaphore, $operators);
}
