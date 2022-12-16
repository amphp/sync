<?php declare(strict_types=1);

namespace Amp\Sync;

class LocalParcelTest extends AbstractParcelTest
{
    protected function createParcel(mixed $value): Parcel
    {
        return new LocalParcel(new LocalMutex(), $value);
    }
}
