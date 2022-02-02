<?php

namespace Amp\Sync;

class LocalParcelTest extends AbstractParcelTest
{
    protected function createParcel(mixed $value): Parcel
    {
        return new LocalParcel(new LocalSemaphore(1), $value);
    }
}
