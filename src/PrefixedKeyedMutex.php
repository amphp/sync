<?php

namespace Amp\Sync;

final class PrefixedKeyedMutex implements KeyedMutex
{
    /** @var KeyedMutex */
    private KeyedMutex $mutex;

    /** @var string */
    private string $prefix;

    public function __construct(KeyedMutex $mutex, string $prefix)
    {
        $this->mutex = $mutex;
        $this->prefix = $prefix;
    }

    public function acquire(string $key): Lock
    {
        return $this->mutex->acquire($this->prefix . $key);
    }
}
