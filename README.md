# amphp/sync

AMPHP is a collection of event-driven libraries for PHP designed with fibers and concurrency in mind.
`amphp/sync` specifically provides synchronization primitives such as locks and semaphores for asynchronous and concurrent programming.

[![Latest Release](https://img.shields.io/github/release/amphp/sync.svg?style=flat-square)](https://github.com/amphp/sync/releases)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://github.com/amphp/sync/blob/master/LICENSE)

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require amphp/sync
```

## Usage

The weak link when managing concurrency is humans; so `amphp/sync` provides abstractions to hide some complexity.

### Mutex

[Mutual exclusion](https://en.wikipedia.org/wiki/Mutual_exclusion) can be achieved using `Amp\Sync\synchronized()` and any `Mutex` implementation, or by manually using the `Mutex` instance to acquire a `Lock`.

As long as the resulting `Lock` object isn't released using `Lock::release()` or by being garbage collected, the holder of the lock can exclusively run some code as long as all other parties running the same code also acquire a lock before doing so.

```php
function writeExclusively(Amp\Sync\Mutex $mutex, string $filePath, string $data) {
    $lock = $mutex->acquire();
    
    try {
        Amp\File\write($filePath, $data);
    } finally {
        $lock->release();
    }
}
```

```php
function writeExclusively(Amp\Sync\Mutex $mutex, string $filePath, string $data) {
    Amp\Sync\synchronized($mutex, fn () => Amp\File\write($filePath, $data));
}
```

### Semaphore

[Semaphores](https://en.wikipedia.org/wiki/Semaphore_%28programming%29) are another synchronization primitive in addition to [mutual exclusion](#mutex).

Instead of providing exclusive access to a single party, they provide access to a limited set of N parties at the same time.
This makes them great to control concurrency, e.g. limiting an HTTP client to X concurrent requests, so the HTTP server doesn't get overwhelmed.

Similar to [`Mutex`](#mutex), `Lock` instances can be acquired using `Semaphore::acquire()`.
Please refer to the [`Mutex`](#mutex) documentation for additional usage documentation, as they're basically equivalent except for the fact that `Mutex` is always a `Semaphore` with a count of exactly one party.

In many cases you can use [`amphp/pipeline`](https://github.com/amphp/pipeline) instead of directly using a `Semaphore`.

### Concurrency Approaches

Given you have a list of URLs you want to crawl, let's discuss a few possible approaches. For simplicity, we will assume a `fetch` function already exists, which takes a URL and returns the HTTP status code (which is everything we want to know for these examples).

#### Approach 1: Sequential

Simple loop using non-blocking I/O, but no concurrency while fetching the individual URLs; starts the second request as soon as the first completed.

```php
$urls = [...];

$results = [];

foreach ($urls as $url) {
    $results[$url] = fetch($url);
}

var_dump($results);
```

#### Approach 2: Everything Concurrently

Almost the same loop, but awaiting all operations at once; starts all requests immediately. Might not be feasible with too many URLs.

```php
$urls = [...];

$results = [];

foreach ($urls as $url) {
    $results[$url] = Amp\async(fetch(...), $url);
}

$results = Amp\Future\await($results);

var_dump($results);
```

#### Approach 3: Concurrent Chunks

Splitting the jobs into chunks of ten; all requests within a chunk are made concurrently, but each chunk sequentially, so the timing for each chunk depends on the slowest response; starts the eleventh request as soon as the first ten requests completed.

```php
$urls = [...];

$results = [];

foreach (\array_chunk($urls, 10) as $chunk) {
    $futures = [];

    foreach ($chunk as $url) {
        $futures[$url] = Amp\async(fetch(...), $url);
    }

    $results = \array_merge($results, Amp\Future\await($futures));
}

var_dump($results);
```

#### Approach 4: ConcurrentIterator

TODO: Link to example of amphp/pipeline

## Versioning

`amphp/sync` follows the [semver](http://semver.org/) semantic versioning specification like all other `amphp` packages.

## Security

If you discover any security related issues, please email [`me@kelunik.com`](mailto:me@kelunik.com) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.
