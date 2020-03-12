<?php

namespace Amp\Sync;

final class NativeSerializer implements Serializer
{
    public function serialize($data): string
    {
        try {
            return \serialize($data);
        } catch (\Throwable $exception) {
            throw new SerializationException(
                \sprintf('The given data could not be serialized: %s', $exception->getMessage()),
                0,
                $exception
            );
        }
    }

    public function unserialize(string $data)
    {
        try {
            $result = \unserialize($data, ['allowed_classes' => true]);

            if ($result === false && $data !== \serialize(false)) {
                throw new SerializationException(
                    'Invalid data provided to unserialize: ' . encodeUnprintableChars($data)
                );
            }
        } catch (\Throwable $exception) {
            throw new SerializationException('Exception thrown when unserializing data', 0, $exception);
        }

        return $result;
    }
}
