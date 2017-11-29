<?php

namespace Amp\Sync;

use Amp\Promise;

/**
 * A non-blocking counting semaphore.
 *
 * Objects that implement this interface should guarantee that all operations
 * are atomic. Implementations do not have to guarantee that acquiring a lock
 * is first-come, first serve.
 */
interface Semaphore {
    /**
     * Acquires a lock on the semaphore.
     *
     * @return \Amp\Promise<\Amp\Sync\Lock> Resolves with an integer keyed lock object when the acquire is
     *    successful. Identifiers returned by the locks should be 0-indexed. Releasing an idenifier MUST make that same
     *    identifier available.
     */
    public function acquire(): Promise;
}
