<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;

final class StaticKeyMutex implements Mutex
{
    use ForbidCloning;
    use ForbidSerialization;

    public function __construct(
        private readonly KeyedMutex $mutex,
        private readonly string $key,
    ) {
    }

    #[\Override]
    public function acquire(?Cancellation $cancellation = null): Lock
    {
        return $this->mutex->acquire($this->key, $cancellation);
    }
}
