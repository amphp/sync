---
title: Synchronization Tools
permalink: /
---
This package defines synchronization primitives for PHP applications and libraries using Amp.

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require amphp/sync
```

## Usage

This package defines two interfaces: `Amp\Sync\Mutex` and `Amp\Sync\Semaphore`.

```php
/**
 * A non-blocking synchronization primitive that can be used for mutual exclusion across contexts.
 *
 * Objects that implement this interface should guarantee that all operations
 * are atomic. Implementations do not have to guarantee that acquiring a lock
 * is first-come, first serve.
 */
interface Mutex {
    /**
     * Acquires a lock on the mutex.
     *
     * @return \Amp\Promise<\Amp\Sync\Lock> Resolves with a lock object when the acquire is successful.
     */
    public function acquire(): Promise;
}
```

```php
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
     * @return \Amp\Promise<\Amp\Sync\KeyedLock> Resolves with an integer keyed lock object when the acquire is
     *    successful. Keys returned by the locks should be 0-indexed. Releasing a key MUST make that same key available.
     */
    public function acquire(): Promise;
}
```

These are very similar. The key difference is that `Mutex` only ever allows one lock while a `Semaphore` might allow `n` locks to be hold at the same time.
