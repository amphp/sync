<?php

namespace Amp\Sync\Test;

use Amp\Sync\BuiltInSerializer;
use Amp\Sync\Serializer;

class BuiltInSerializerTest extends AbstractSerializerTest
{
    public function createSerializer(): Serializer
    {
        return new BuiltInSerializer;
    }
}
