<?php

namespace Amp\Sync;

use Amp\Promise;
use function Amp\call;

/**
 * A handle on an acquired lock from a synchronization object.
 * This object is not thread-safe; after acquiring a lock from a mutex or
 * semaphore, the lock should reside in the same thread or process until it is
 * released.
 */
final class Lock {
    /** @var callable|null The function to be called on release or null if the lock has been released. */
    private $releaser;

    /** @var int */
    private $id;

    /** @var Promise|null */
    private $released;

    /**
     * Creates a new lock permit object.
     *
     * @param int $id The lock identifier.
     * @param callable $releaser A function to be called upon release. The function will be passed this object as the
     *     first parameter.
     */
    public function __construct(int $id, callable $releaser) {
        $this->id = $id;
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
     * @return int Lock identifier.
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Releases the lock. No-op if the lock has already been released.
     */
    public function release(): Promise {
        if (!$this->releaser) {
            return $this->released;
        }

        // Invoke the releaser function given to us by the synchronization source
        // to release the lock.
        $releaser = $this->releaser;
        $this->releaser = null;
        return $this->released = call($releaser, $this);
    }

    /**
     * Releases the lock when there are no more references to it.
     */
    public function __destruct() {
        if ($this->releaser) {
            Promise\rethrow($this->release());
        }
    }
}
