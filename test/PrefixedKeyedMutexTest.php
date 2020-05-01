<?php

namespace Amp\Sync\Tests;

use Amp\Sync\KeyedMutex;
use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\PrefixedKeyedMutex;
use Amp\Sync\Test\AbstractKeyedMutexTest;

class PrefixedKeyedMutexTest extends AbstractKeyedMutexTest
{
    public function createMutex(): KeyedMutex
    {
        return new PrefixedKeyedMutex(new LocalKeyedMutex, 'prefix.');
    }
}
