<?php

namespace Amp\Sync;

/**
 * A non-blocking counting semaphore.
 *
 * Objects that implement this interface should guarantee that all operations
 * are atomic. Implementations do not have to guarantee that acquiring a lock
 * is first-come, first serve.
 */
interface Semaphore extends Mutex {
    // Same interface as Mutex, but multiple locks are allowed.
}
