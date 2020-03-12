<?php

namespace Amp\Sync\Test;

use Amp\Sync\BuiltInSerializer;
use Amp\Sync\CompressingSerializer;
use Amp\Sync\Serializer;

class CompressingSerializerTest extends AbstractSerializerTest
{
    public function createSerializer(): Serializer
    {
        return new CompressingSerializer(new BuiltInSerializer);
    }
}
