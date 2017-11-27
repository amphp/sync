<?php

namespace Amp\Sync;

use Amp\Delayed;
use Amp\Promise;
use function Amp\call;

/**
 * A thread-safe, asynchronous mutex using the pthreads locking mechanism.
 *
 * Compatible with POSIX systems and Microsoft Windows.
 */
class ThreadedMutex implements Mutex {
    /** @var \Threaded */
    private $mutex;

    /**
     * Creates a new threaded mutex.
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initializes the mutex.
     */
    private function init() {
        $this->mutex = new class extends \Threaded {
            const LATENCY_TIMEOUT =  10;

            /** @var bool */
            private $lock = true;

            /**
             * @return \Amp\Promise
             */
            public function acquire(): Promise {
                return call(function () {
                    $tsl = function () {
                        return ($this->lock ? $this->lock = false : true);
                    };

                    while (!$this->lock || $this->synchronized($tsl)) {
                        yield new Delayed(self::LATENCY_TIMEOUT);
                    }

                    return new Lock(function () {
                        $this->release();
                    });
                });
            }

            /**
             * Releases the lock.
             */
            protected function release() {
                $this->lock = true;
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function acquire(): Promise {
        return $this->mutex->acquire();
    }

    /**
     * Makes a copy of the mutex in the unlocked state.
     */
    public function __clone() {
        $this->init();
    }
}
