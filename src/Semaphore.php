<?php declare(strict_types=1);

namespace Amp\Sync;

/**
 * A counting semaphore.
 *
 * Objects that implement this interface should guarantee that all operations are atomic. Implementations do not have to
 * guarantee that acquiring a lock is first-come, first serve.
 */
interface Semaphore
{
    /**
     * Acquires a lock on the semaphore. Semaphores may have one or more locks.
     *
     * @return Lock Returns with a lock object once a lock is obtained. May fail with a SyncException if an
     *     error occurs when attempting to obtain the lock (e.g. a shared memory segment closed).
     */
    public function acquire(): Lock;
}
