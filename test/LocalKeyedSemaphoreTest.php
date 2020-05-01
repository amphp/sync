<?php

namespace Amp\Sync\Tests;

use Amp\Sync\KeyedSemaphore;
use Amp\Sync\LocalKeyedSemaphore;
use Amp\Sync\Test\AbstractKeyedSemaphoreTest;

class LocalKeyedSemaphoreTest extends AbstractKeyedSemaphoreTest
{
    public function createSemaphore(int $size): KeyedSemaphore
    {
        return new LocalKeyedSemaphore($size);
    }
}
