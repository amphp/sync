<?php

namespace Amp\Sync;

class LocalKeyedSemaphoreTest extends AbstractKeyedSemaphoreTest
{
    public function createSemaphore(int $size): KeyedSemaphore
    {
        return new LocalKeyedSemaphore($size);
    }
}
