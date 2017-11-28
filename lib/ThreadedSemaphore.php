<?php

namespace Amp\Sync;

use Amp\Delayed;
use Amp\Promise;
use function Amp\call;

/**
 * An asynchronous semaphore based on pthreads' synchronization methods.
 *
 * This is an implementation of a thread-safe semaphore that has non-blocking
 * acquire methods. There is a small tradeoff for asynchronous semaphores; you
 * may not acquire a lock immediately when one is available and there may be a
 * small delay. However, the small delay will not block the thread.
 */
class ThreadedSemaphore implements Semaphore {
    /** @var \Threaded */
    private $semaphore;

    /**
     * Creates a new semaphore with a given number of locks.
     *
     * @param int $locks The maximum number of locks that can be acquired from the semaphore.
     */
    public function __construct(int $locks) {
        if ($locks < 1) {
            throw new \Error("The number of locks should be a positive integer");
        }

        $this->semaphore = new class($locks) extends \Threaded {
            const LATENCY_TIMEOUT = 10;

            /** @var int The number of available locks. */
            private $locks;

            /**
             * Creates a new semaphore with a given number of locks.
             *
             * @param int $locks The maximum number of locks that can be acquired from the semaphore.
             */
            public function __construct(int $locks) {
                $this->locks = $locks;
            }

            /**
             * Gets the number of currently available locks.
             *
             * @return int The number of available locks.
             */
            public function getAvailable(): int {
                return $this->locks;
            }

            /**
             * @return \Amp\Promise
             */
            public function acquire(): Promise {
                /**
                 * Uses a double locking mechanism to acquire a lock without blocking. A
                 * synchronous mutex is used to make sure that the semaphore is queried one
                 * at a time to preserve the integrity of the semaphore itself. Then a lock
                 * count is used to check if a lock is available without blocking.
                 *
                 * If a lock is not available, we add the request to a queue and set a timer
                 * to check again in the future.
                 */
                return call(function () {
                    $tsl = function () {
                        // If there are no locks available or the wait queue is not empty,
                        // we need to wait our turn to acquire a lock.
                        if ($this->locks > 0) {
                            --$this->locks;
                            return false;
                        }
                        return true;
                    };

                    while ($this->locks < 1 || $this->synchronized($tsl)) {
                        yield new Delayed(self::LATENCY_TIMEOUT);
                    }

                    return new Lock(function () {
                        $this->release();
                    });
                });
            }

            /**
             * Releases a lock from the semaphore.
             */
            protected function release() {
                $this->synchronized(function () {
                    ++$this->locks;
                });
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function acquire(): Promise {
        return $this->semaphore->acquire();
    }
}
