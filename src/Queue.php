<?php

namespace Amp\Sync;

use Amp\Iterator;
use Amp\Promise;
use function Amp\asyncCall;
use function Amp\call;

final class Queue
{
    public static function fromIterator(Iterator $iterator)
    {
        return new self($iterator);
    }

    /** @var Iterator */
    private $iterator;

    /** @var bool */
    private $processing = false;

    /** @var Promise[] */
    private $pending = [];

    private function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    public function process(Semaphore $semaphore, callable $processor, callable $errorHandler): Promise
    {
        return call(function () use ($semaphore, $processor, $errorHandler) {
            if ($this->processing) {
                throw new \Error(__METHOD__ . '() can only be called once');
            }

            $this->processing = true;

            while (yield $this->iterator->advance()) {
                /** @var Lock $lock */
                $lock = yield $semaphore->acquire();

                $job = $this->iterator->getCurrent();

                $promise = call(static function () use ($lock, $job, $processor, $errorHandler) {
                    try {
                        yield call($processor, $job);
                    } catch (\Throwable $e) {
                        asyncCall($errorHandler, $e, $job);
                    } finally {
                        $lock->release();
                    }
                });

                $promiseId = \spl_object_id($promise);

                $this->pending[$promiseId] = $promise;
                $promise->onResolve(function () use ($promiseId) {
                    unset($this->pending[$promiseId]);
                });
            }

            yield Promise\any($this->pending);
        });
    }
}