<?php declare(strict_types=1);

namespace Amp\Sync;

class PrefixedKeyedMutexTest extends AbstractKeyedMutexTest
{
    #[\Override]
    protected function createMutex(): KeyedMutex
    {
        return new PrefixedKeyedMutex(new LocalKeyedMutex(), 'prefix.');
    }
}
