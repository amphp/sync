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
        $this->mutex = new class extends \Threaded {
            const LATENCY_TIMEOUT =  10;

            /** @var bool */
            private $locked = false;

            /**
             * @return \Amp\Promise
             */
            public function acquire(): Promise {
                return call(function () {
                    $tsl = function () {
                        return (!$this->locked ? $this->locked = true : false);
                    };

                    while ($this->locked || $this->synchronized($tsl)) {
                        yield new Delayed(self::LATENCY_TIMEOUT);
                    }

                    return new Lock(0, function () {
                        $this->locked = false;
                    });
                });
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function acquire(): Promise {
        return $this->mutex->acquire();
    }
}
