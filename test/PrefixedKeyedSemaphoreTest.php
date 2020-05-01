<?php

namespace Amp\Sync\Tests;

use Amp\Sync\KeyedSemaphore;
use Amp\Sync\LocalKeyedSemaphore;
use Amp\Sync\PrefixedKeyedSemaphore;
use Amp\Sync\Test\AbstractKeyedSemaphoreTest;

class PrefixedKeyedSemaphoreTest extends AbstractKeyedSemaphoreTest
{
    public function createSemaphore(int $size): KeyedSemaphore
    {
        return new PrefixedKeyedSemaphore(new LocalKeyedSemaphore($size), 'prefix.');
    }
}
