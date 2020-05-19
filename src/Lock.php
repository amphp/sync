<?php

namespace Amp\Sync;

use Amp\Promise;
use InvalidArgumentException;

/**
 * A handle on an acquired lock from a synchronization object.
 *
 * This object is not thread-safe; after acquiring a lock from a mutex or
 * semaphore, the lock should reside in the same thread or process until it is
 * released.
 */
class Lock
{
    /** @var callable(self): Promise<void> The function to be called on release or null if the lock has been released. */
    private $releaser;

    /** @var int */
    private $id;

    /** @var Promise|null The promise returned by the releaser. */
    private $promise;

    /**
     * Creates a new lock permit object.
     *
     * @param int $id The lock identifier.
     * @param callable(self): Promise<void> $releaser A function to be called upon release.
     */
    public function __construct(int $id, callable $releaser)
    {
        $this->id = $id;
        $this->releaser = $releaser;
    }

    /**
     * Checks if the lock has already been released.
     *
     * @return bool True if the lock has already been released, otherwise false.
     */
    public function isReleased(): bool
    {
        return $this->promise !== null;
    }

    /**
     * @return int Lock identifier.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Releases the lock. No-op if the lock has already been released.
     *
     * @return Promise<void>
     */
    public function release(): Promise
    {
        if ($this->promise) {
            return $this->promise;
        }

        // Invoke the releaser function given to us by the synchronization source
        // to release the lock.
        $this->promise = Promise\call(($this->releaser)($this));

        return $this->promise;
    }

    /**
     * Releases the lock when there are no more references to it.
     */
    public function __destruct()
    {
        if (!$this->isReleased()) {
            $this->release();
        }
    }
}
