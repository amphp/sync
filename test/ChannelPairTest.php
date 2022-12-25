<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\PHPUnit\AsyncTestCase;
use function Amp\async;

class ChannelPairTest extends AsyncTestCase
{
    public function testPair(): void
    {
        $values = [1, 2, 3];
        [$left, $right] = createChannelPair();

        $future = async(function () use ($right, $values): void {
            foreach ($values as $value) {
                $right->send($value);
            }

            $right->close();
        });

        foreach ($left as $received) {
            self::assertSame(\array_shift($values), $received);
        }

        $future->await();
    }

    public function testClosingReceivingChannel(): void
    {
        [$left, $right] = createChannelPair();
        $left->close();

        $this->expectException(ChannelException::class);
        $this->expectExceptionMessage('channel closed');

        $left->receive();
    }

    public function testClosingSendingChannel(): void
    {
        [$left, $right] = createChannelPair();
        $future = async($left->receive(...));
        async($right->close(...))->ignore();

        $this->expectException(ChannelException::class);
        $this->expectExceptionMessage('channel closed');

        $future->await();
    }

    public function testSendingNull(): void
    {
        [$left, $right] = createChannelPair();

        $future = async($left->receive(...));
        $right->send(null);

        self::assertNull($future->await());
    }
}
