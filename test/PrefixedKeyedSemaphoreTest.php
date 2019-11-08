<?php

namespace Amp\Sync\Test;

use Amp\Sync\KeyedSemaphore;
use Amp\Sync\LocalKeyedSemaphore;
use Amp\Sync\PrefixedKeyedSemaphore;

class PrefixedKeyedSemaphoreTest extends AbstractKeyedSemaphoreTest
{
    public function createSemaphore(int $size): KeyedSemaphore
    {
        return new PrefixedKeyedSemaphore(new LocalKeyedSemaphore($size), 'prefix.');
    }
}
