<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Pipeline\Pipeline;

class RateLimitingSemaphoreTest extends AsyncTestCase
{
    public function providePeriods(): iterable
    {
        yield 'one-lock-no-delay' => [0.1, 0, 3, 1];
        yield 'one-lock-with-delay' => [0.1, 0.05, 3, 1];
        yield 'multiple-locks-no-delay' => [0.1, 0, 10, 3];
        yield 'multiple-locks-with-delay' => [0.1, 0.05, 10, 3];
        yield 'multiple-locks-long-delay' => [0.05, 0.1, 10, 3];
    }

    /**
     * @dataProvider providePeriods
     */
    public function testSemaphore(float $lockPeriod, float $delay, int $cycles, int $numLocks): void
    {
        if ($cycles < $numLocks) {
            $this->fail('Must have more locks than test cycles');
        }

        $semaphore = new RateLimitingSemaphore(new LocalSemaphore($numLocks), $lockPeriod);

        $this->setMinimumRuntime(\max($lockPeriod * (($cycles - 1) / $numLocks), $delay * $cycles));

        Pipeline::fromIterable(\range(1, $cycles))
            ->delay($delay)
            ->forEach(fn () => $semaphore->acquire()->release());
    }

    public function testInvalidLockPeriod(): void
    {
        $this->expectException(\ValueError::class);
        new RateLimitingSemaphore(new LocalSemaphore(1), 0);
    }
}
