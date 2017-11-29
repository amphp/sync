<?php

namespace Amp\Sync;

/**
 * A handle on an acquired lock from a synchronization object.
 *
 * This object is not thread-safe; after acquiring a lock from a mutex or
 * semaphore, the lock should reside in the same thread or process until it is
 * released.
 */
class Lock {
    /** @var callable|null The function to be called on release or null if the lock has been released. */
    private $releaser;

    /**
     * Creates a new lock permit object.
     *
     * @param callable $releaser A function to be called upon release. The function will be passed this object as the
     *     first parameter.
     */
    public function __construct(callable $releaser) {
        $this->releaser = $releaser;
    }

    /**
     * Checks if the lock has already been released.
     *
     * @return bool True if the lock has already been released, otherwise false.
     */
    public function isReleased(): bool {
        return !$this->releaser;
    }

    /**
     * Releases the lock. No-op if the lock has already been released.
     */
    public function release() {
        if (!$this->releaser) {
            return;
        }

        // Invoke the releaser function given to us by the synchronization source
        // to release the lock.
        $releaser = $this->releaser;
        $this->releaser = null;
        ($releaser)($this);
    }

    /**
     * Releases the lock when there are no more references to it.
     */
    public function __destruct() {
        if ($this->releaser) {
            $this->release();
        }
    }
}
