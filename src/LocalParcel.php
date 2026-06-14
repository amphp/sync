<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\ForbidCloning;
use Amp\ForbidSerialization;

/**
 * @template T
 * @template-implements Parcel<T>
 */
final class LocalParcel implements Parcel
{
    use ForbidCloning;
    use ForbidSerialization;

    /**
     * @param T $value
     */
    public function __construct(
        private readonly Mutex $mutex,
        private mixed $value,
    ) {
    }

    #[\Override]
    public function synchronized(\Closure $closure): mixed
    {
        try {
            $lock = $this->mutex->acquire();
        } catch (SyncException $exception) {
            throw new ParcelException("Failed to acquire lock", previous: $exception);
        }

        try {
            $this->value = $closure($this->value);
        } finally {
            $lock->release();
        }

        return $this->value;
    }

    #[\Override]
    public function unwrap(): mixed
    {
        return $this->value;
    }
}
