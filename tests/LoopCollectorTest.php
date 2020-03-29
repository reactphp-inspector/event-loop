<?php declare(strict_types=1);

namespace ReactInspector\Tests\EventLoop;

use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;
use ReactInspector\EventLoop\LoopCollector;
use ReactInspector\EventLoop\LoopDecorator;
use ReactInspector\Measurement;
use ReactInspector\Metric;
use ReactInspector\Tag;
use Rx\Observable;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use function ApiClients\Tools\Rx\observableFromArray;
use function array_filter;
use function count;
use function Safe\fopen;

/** @internal */
final class LoopCollectorTest extends AsyncTestCase
{
    public const STREAM_READ   = 'r+';
    public const STREAM_WRITE  = 'w+';
    public const STREAM_DUPLEX = 'a+';

    protected LoopInterface $loop;

    protected LoopCollector $infoProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loop         = new LoopDecorator(new StreamSelectLoop());
        $this->infoProvider = new LoopCollector($this->loop);
    }

    public function testFutureTick(): void
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'ticks'), $this->loop);
        self::assertCount(3, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value());
        }

        $this->loop->futureTick(static function (): void {
        });

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_ticks', 'ticks')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(3, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['future_tick_state' => 'active'])) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->run();

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'ticks'), $this->loop);
        self::assertCount(3, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['future_tick_state' => 'active'])) {
                self::assertSame(0.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(1.0, $measurement->value(), (string) $measurement);
        }
    }

    public function testTimer(): void
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_timers', 'timers'), $this->loop);
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value());
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->addTimer(0.0001, static function (): void {
        });

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_timers', 'timers')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['timer_state' => 'active', 'timer_kind' => 'once'])) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->run();

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_timers', 'timers'), $this->loop);
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['timer_state' => 'done', 'timer_kind' => 'once'])) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['timer_kind' => 'once'])) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }
    }

    public function testTimerCanceledBeforeCalled(): void
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_timers', 'timers'), $this->loop);
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value());
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $timer = $this->loop->addTimer(1, static function (): void {
        });

        $this->loop->futureTick(function () use ($timer): void {
            $this->loop->cancelTimer($timer);
            $this->loop->cancelTimer($timer);
        });

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_timers', 'timers')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['timer_state' => 'active', 'timer_kind' => 'once'])) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->run();

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_timers', 'timers')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['timer_state' => 'done', 'timer_kind' => 'once'])) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }
    }

    public function testPeriodicTimer(): void
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_timers', 'timers'), $this->loop);
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value());
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $i = 1;
        $this->loop->addPeriodicTimer(0.0001, function (TimerInterface $timer) use (&$i): void {
            if ($i === 3) {
                $this->loop->cancelTimer($timer);
            }

            $i++;
        });

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_timers', 'timers')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['timer_state' => 'active', 'timer_kind' => 'periodic'])) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->run();

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_timers', 'timers'), $this->loop);
        self::assertCount(4, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['timer_state' => 'done', 'timer_kind' => 'periodic'])) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_ticks', 'timers'), $this->loop);
        self::assertCount(2, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['timer_kind' => 'periodic'])) {
                self::assertSame(3.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }
    }

    public function testAddReadStream(): void
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_streams', 'streams'), $this->loop);
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value());
        }

        $stream = $this->createStream(self::STREAM_READ);

        $this->loop->addReadStream($stream, static function (): void {
        });

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_streams', 'streams'), $this->loop);
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'active']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'read', 'stream_state' => 'active'])
            ) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->removeReadStream($stream);

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_streams', 'streams'), $this->loop);
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'done']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'read', 'stream_state' => 'done'])
            ) {
//                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

//            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }
    }

    public function testAddWriteStream(): void
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_streams', 'streams'), $this->loop);
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value());
        }

        $stream = $this->createStream(self::STREAM_WRITE);

        $this->loop->addWriteStream($stream, static function (): void {
        });

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_streams', 'streams'), $this->loop);
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'active']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'write', 'stream_state' => 'active'])
            ) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->removeWriteStream($stream);

        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_streams', 'streams'), $this->loop);
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'done']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'write', 'stream_state' => 'done'])
            ) {
//                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

//            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }
    }

    public function testComplexReadWriteDuplexStreams(): void
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->await($this->getMeasurements('reactphp_streams', 'streams'), $this->loop);
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            self::assertSame(0.0, $measurement->value());
        }

        $streamRead   = $this->createStream(self::STREAM_READ);
        $streamWrite  = $this->createStream(self::STREAM_WRITE);
        $streamDuplex = $this->createStream(self::STREAM_DUPLEX);

        $this->loop->addReadStream($streamRead, static function (): void {
        });
        $this->loop->addWriteStream($streamWrite, static function (): void {
        });

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_streams', 'streams')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'active'])) {
                self::assertSame(2.0, $measurement->value(), (string) $measurement);
                continue;
            }

            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'read', 'stream_state' => 'active']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'write', 'stream_state' => 'active'])
            ) {
                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->addReadStream($streamDuplex, static function (): void {
        });
        $this->loop->addWriteStream($streamDuplex, static function (): void {
        });

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_streams', 'streams')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'active'])) {
                self::assertSame(3.0, $measurement->value(), (string) $measurement);
                continue;
            }

            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'read', 'stream_state' => 'active']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'write', 'stream_state' => 'active'])
            ) {
                self::assertSame(2.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->removeReadStream($streamRead);
        $this->loop->removeWriteStream($streamWrite);

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_streams', 'streams')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'done'])) {
//                self::assertSame(2.0, $measurement->value(), (string) $measurement);
                continue;
            }

//            self::assertSame(1.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->removeReadStream($streamDuplex);

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_streams', 'streams')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'done']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'read', 'stream_state' => 'done'])
            ) {
//                self::assertSame(2.0, $measurement->value(), (string) $measurement);
                continue;
            }

            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'active']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'write', 'stream_state' => 'active']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'write', 'stream_state' => 'done'])
            ) {
//                self::assertSame(1.0, $measurement->value(), (string) $measurement);
                continue;
            }

            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }

        $this->loop->removeWriteStream($streamDuplex);

        /** @var Measurement[] $measurements */
        $measurements = null;
        $this->getMeasurements('reactphp_streams', 'streams')->then(static function (array $mm) use (&$measurements): void {
            $measurements = $mm;
        });
        self::assertCount(6, $measurements);
        foreach ($measurements as $measurement) {
            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'duplex', 'stream_state' => 'done'])) {
//                self::assertSame(3.0, $measurement->value(), (string) $measurement);
                continue;
            }

            if ($this->hasTagAndValue($measurement, ['stream_kind' => 'read', 'stream_state' => 'done']) ||
                $this->hasTagAndValue($measurement, ['stream_kind' => 'write', 'stream_state' => 'done'])
            ) {
                self::assertSame(2.0, $measurement->value(), (string) $measurement);
                continue;
            }

//            self::assertSame(0.0, $measurement->value(), (string) $measurement);
        }
    }

    /**
     * @return resource
     */
    private function createStream(string $mode)
    {
        return fopen('php://temp', $mode);
    }

    private function getMeasurements(string $metricName, string $component): PromiseInterface
    {
        return $this->infoProvider->collect()->filter(static function (Metric $metric) use ($metricName): bool {
            return $metric->config()->name() === $metricName;
        })->flatMap(static function (Metric $metric): Observable {
            return observableFromArray($metric->measurements()->get());
        })->filter(static function (Measurement $measurement) use ($component): bool {
            return count(array_filter($measurement->tags()->get(), static function (Tag $tag) use ($component): bool {
                return $tag->key() === 'event_loop_component' && $tag->value() === $component;
            })) > 0;
        })->toArray()->toPromise();
    }

    /**
     * @param array<string, string> $keyValuePairs
     */
    private function hasTagAndValue(Measurement $measurement, array $keyValuePairs): bool
    {
        $tags = $measurement->tags()->get();

        foreach ($keyValuePairs as $key => $value) {
            foreach ($tags as $tag) {
                if ($tag->key() === $key && $tag->value() === $value) {
                    continue 2;
                }
            }

            return false;
        }

        return true;
    }
}
