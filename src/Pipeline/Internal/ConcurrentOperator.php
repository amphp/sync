<?php

namespace Amp\Sync\Pipeline\Internal;

use Amp\DeferredFuture;
use Amp\Future;
use Amp\Pipeline\Emitter;
use Amp\Pipeline\Pipeline;
use Amp\Pipeline\PipelineOperator;
use Amp\Sync\Lock;
use Amp\Sync\Semaphore;
use Revolt\EventLoop;

/**
 * @template TValue
 * @template-implements PipelineOperator<TValue, TValue>
 *
 * @internal
 */
final class ConcurrentOperator implements PipelineOperator
{
    /**
     * @param Semaphore $semaphore Concurrency limited to number of locks provided by the semaphore.
     * @param PipelineOperator[] $operators Set of operators to apply to each concurrent pipeline.
     */
    public function __construct(
        private Semaphore $semaphore,
        private array $operators,
    ) {
    }

    public function pipe(Pipeline $pipeline): Pipeline
    {
        $destination = new Emitter();

        EventLoop::queue(function () use ($pipeline, $destination): void {
            $queue = new \SplQueue();
            $emitters = new \SplObjectStorage();
            $pending = 0;
            $previous = Future::complete();

            try {
                foreach ($pipeline as $value) {
                    $lock = $this->semaphore->acquire();

                    if ($destination->isComplete() || $destination->isDisposed()) {
                        return;
                    }

                    if ($queue->isEmpty()) {
                        $emitter = $this->createEmitter($destination, $queue, $emitters, $pending);
                    } else {
                        $emitter = $queue->shift();
                    }

                    $previous->ignore();
                    $previous = $emitter->emit([$value, $lock]);
                }
            } catch (\Throwable $exception) {
                try {
                    $previous->await();
                } catch (\Throwable) {
                    // Exception ignored in case destination is disposed while waiting.
                }

                if (!$destination->isComplete()) {
                    $destination->error($exception);
                }
            } finally {
                if ($pending === 0 && !$destination->isComplete()) {
                    $destination->complete();
                }

                /** @var Emitter $emitter */
                foreach ($emitters as $emitter) {
                    $emitter->complete();
                }
            }
        });

        return $destination->pipe();
    }

    private function createEmitter(
        Emitter $destination,
        \SplQueue $queue,
        \SplObjectStorage $emitters,
        int &$pending
    ): Emitter {
        $emitter = new Emitter();
        $emitters->attach($emitter);

        EventLoop::queue(function () use (&$pending, $emitters, $emitter, $destination, $queue): void {
            $operatorEmitter = new Emitter();
            $operatorPipeline = $operatorEmitter->pipe();
            foreach ($this->operators as $operator) {
                $operatorPipeline = $operator->pipe($operatorPipeline);
            }

            try {
                /**
                 * @var TValue $value
                 * @var Lock $lock
                 * @var Future $previous
                 * @var DeferredFuture $deferred
                 */
                foreach ($emitter->pipe() as [$value, $lock]) {
                    ++$pending;
                    EventLoop::queue(static function () use (
                        &$pending,
                        $emitter,
                        $queue,
                        $emitters,
                        $operatorEmitter,
                        $operatorPipeline,
                        $value,
                        $destination,
                    ): void {
                        try {
                            if (null === $value = $operatorPipeline->continue()) {
                                return;
                            }

                            if ($destination->isComplete()) {
                                return;
                            }

                            $destination->yield($value);
                        } catch (\Throwable $exception) {
                            if (!$destination->isComplete()) {
                                $destination->error($exception);
                            }
                            $operatorPipeline->dispose();
                            return;
                        } finally {
                            --$pending;
                        }

                        \assert($pending >= 0);

                        if ($pending === 0 && $emitter->isComplete() && !$destination->isComplete()) {
                            $destination->complete();
                        }
                    });

                    try {
                        $operatorEmitter->yield($value);
                    } finally {
                        $queue->push($emitter);
                        $lock->release();
                    }
                }

                $operatorEmitter->complete();
            } catch (\Throwable $exception) {
                $operatorEmitter->error($exception);
                if (!$destination->isComplete()) {
                    $destination->error($exception);
                }
            }
        });

        return $emitter;
    }
}
