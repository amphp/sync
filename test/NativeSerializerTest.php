<?php

namespace Amp\Sync\Test;

use Amp\Sync\NativeSerializer;
use Amp\Sync\Serializer;

class NativeSerializerTest extends AbstractSerializerTest
{
    public function createSerializer(): Serializer
    {
        return new NativeSerializer;
    }
}
