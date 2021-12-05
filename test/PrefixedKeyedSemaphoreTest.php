<?php

namespace Amp\Sync;

class PrefixedKeyedSemaphoreTest extends AbstractKeyedSemaphoreTest
{
    public function createSemaphore(int $size): KeyedSemaphore
    {
        return new PrefixedKeyedSemaphore(new LocalKeyedSemaphore($size), 'prefix.');
    }
}
