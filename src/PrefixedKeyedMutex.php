<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;

final class PrefixedKeyedMutex implements KeyedMutex
{
    use ForbidCloning;
    use ForbidSerialization;

    public function __construct(
        private readonly KeyedMutex $mutex,
        private readonly string $prefix,
    ) {
    }

    #[\Override]
    public function acquire(string $key, ?Cancellation $cancellation = null): Lock
    {
        return $this->mutex->acquire($this->prefix . $key, $cancellation);
    }
}
