<?php

namespace Amp\Sync;

class PrefixedKeyedMutexTest extends AbstractKeyedMutexTest
{
    public function createMutex(): KeyedMutex
    {
        return new PrefixedKeyedMutex(new LocalKeyedMutex, 'prefix.');
    }
}
