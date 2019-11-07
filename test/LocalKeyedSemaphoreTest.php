<?php

namespace Amp\Sync\Test;

use Amp\Sync\KeyedSemaphore;
use Amp\Sync\LocalKeyedSemaphore;

class LocalKeyedSemaphoreTest extends AbstractKeyedSemaphoreTest
{
    public function createSemaphore(int $size): KeyedSemaphore
    {
        return new LocalKeyedSemaphore($size);
    }
}
