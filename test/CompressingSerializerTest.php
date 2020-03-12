<?php

namespace Amp\Sync\Test;

use Amp\Sync\CompressingSerializer;
use Amp\Sync\NativeSerializer;
use Amp\Sync\Serializer;

class CompressingSerializerTest extends AbstractSerializerTest
{
    public function createSerializer(): Serializer
    {
        return new CompressingSerializer(new NativeSerializer);
    }
}
