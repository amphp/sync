<?php

namespace Amp\Sync\Test;

use Amp\Sync\KeyedMutex;
use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\PrefixedKeyedMutex;

class PrefixedKeyedMutexTest extends AbstractKeyedMutexTest
{
    public function createMutex(): KeyedMutex
    {
        return new PrefixedKeyedMutex(new LocalKeyedMutex, 'prefix.');
    }
}
