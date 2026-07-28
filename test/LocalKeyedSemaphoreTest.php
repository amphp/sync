<?php declare(strict_types=1);

namespace Amp\Sync;

class LocalKeyedSemaphoreTest extends AbstractKeyedSemaphoreTest
{
    #[\Override]
    protected function createSemaphore(int $size): KeyedSemaphore
    {
        return new LocalKeyedSemaphore($size);
    }
}
