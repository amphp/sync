<?php declare(strict_types=1);

namespace Amp\Sync;

class LocalKeyedMutexTest extends AbstractKeyedMutexTest
{
    #[\Override]
    protected function createMutex(): KeyedMutex
    {
        return new LocalKeyedMutex();
    }
}
