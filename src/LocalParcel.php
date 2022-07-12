<?php

namespace Amp\Sync;

/**
 * @template T
 * @template-implements Parcel<T>
 */
final class LocalParcel implements Parcel
{
    /**
     * @param T $value
     */
    public function __construct(
        private Mutex $mutex,
        private mixed $value,
    ) {
    }

    public function synchronized(\Closure $closure): mixed
    {
        $lock = $this->mutex->acquire();

        try {
            $this->value = $closure($this->value);
        } finally {
            $lock->release();
        }

        return $this->value;
    }

    public function unwrap(): mixed
    {
        return $this->value;
    }
}
