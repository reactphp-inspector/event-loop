<?php

declare(strict_types=1);

namespace ReactInspector\Tests\EventLoop;

use Mockery;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use ReactInspector\EventLoop\LoopDecorator;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\Metrics\Configuration;
use WyriHaximus\Metrics\InMemory\Registry;
use WyriHaximus\Metrics\Printer\Prometheus;

use const SIGINT;

final class LoopDecoratorTest extends AsyncTestCase
{
    private int $signal = -1;

    /** @test */
    public function addReadStream(): void
    {
        $stream = 'abc';

        $loop          = Mockery::mock(LoopInterface::class);
        $registry      = new Registry(Configuration::create());
        $decoratedLoop = new LoopDecorator($loop, $registry);

        $listener = static function ($passedStream, $passedLoop) use ($stream, $decoratedLoop): void {
            self::assertSame($stream, $passedStream);
            self::assertSame($decoratedLoop, $passedLoop);
        };
        $loop->shouldReceive('addReadStream')->withArgs(static function ($stream, callable $listener) use ($decoratedLoop): bool {
            $listener($stream, $decoratedLoop);

            return true;
        });
        $loop->shouldReceive('removeReadStream')->with($stream);

        self::assertStringNotContainsString('react_event_loop_stream_ticks_total', $registry->print(new Prometheus()));
        self::assertStringNotContainsString('react_event_loop_streams', $registry->print(new Prometheus()));

        /** @phpstan-ignore-next-line */
        $decoratedLoop->addReadStream($stream, $listener);

        self::assertStringContainsString('react_event_loop_stream_ticks_total{kind="read"} 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_streams{kind="read"} 1', $registry->print(new Prometheus()));

        /** @phpstan-ignore-next-line */
        $decoratedLoop->removeReadStream($stream);

        self::assertStringContainsString('react_event_loop_stream_ticks_total{kind="read"} 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_streams{kind="read"} 0', $registry->print(new Prometheus()));
    }

    /** @test */
    public function addWriteStream(): void
    {
        $stream = 'abc';

        $loop          = Mockery::mock(LoopInterface::class);
        $registry      = new Registry(Configuration::create());
        $decoratedLoop = new LoopDecorator($loop, $registry);

        $listener = static function ($passedStream, $passedLoop) use ($stream, $decoratedLoop): void {
            self::assertSame($stream, $passedStream);
            self::assertSame($decoratedLoop, $passedLoop);
        };
        $loop->shouldReceive('addWriteStream')->withArgs(static function ($stream, callable $listener) use ($decoratedLoop): bool {
            $listener($stream, $decoratedLoop);

            return true;
        });
        $loop->shouldReceive('removeWriteStream')->with($stream);

        self::assertStringNotContainsString('react_event_loop_stream_ticks_total', $registry->print(new Prometheus()));
        self::assertStringNotContainsString('react_event_loop_streams', $registry->print(new Prometheus()));

        /** @phpstan-ignore-next-line */
        $decoratedLoop->addWriteStream($stream, $listener);

        self::assertStringContainsString('react_event_loop_stream_ticks_total{kind="write"} 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_streams{kind="write"} 1', $registry->print(new Prometheus()));

        /** @phpstan-ignore-next-line */
        $decoratedLoop->removeWriteStream($stream);

        self::assertStringContainsString('react_event_loop_stream_ticks_total{kind="write"} 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_streams{kind="write"} 0', $registry->print(new Prometheus()));
    }

    /** @test */
    public function addTimer(): void
    {
        $loop          = Loop::get();
        $registry      = new Registry(Configuration::create());
        $decoratedLoop = new LoopDecorator($loop, $registry);

        $interval = 0.123;
        $listener = static function (): void {
        };

        self::assertStringNotContainsString('react_event_loop_timer_ticks_total', $registry->print(new Prometheus()));
        self::assertStringNotContainsString('react_event_loop_timers', $registry->print(new Prometheus()));

        $decoratedLoop->addTimer($interval, $listener);

        self::assertStringNotContainsString('react_event_loop_timer_ticks_total', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_timers{kind="one-off"} 1', $registry->print(new Prometheus()));

        $decoratedLoop->run();

        self::assertStringContainsString('react_event_loop_timer_ticks_total{kind="one-off"} 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_timers{kind="one-off"} 0', $registry->print(new Prometheus()));
    }

    /** @test */
    public function addPeriodicTimer(): void
    {
        $loop          = Loop::get();
        $registry      = new Registry(Configuration::create());
        $decoratedLoop = new LoopDecorator($loop, $registry);

        $interval = 0.123;
        $listener = static function ($timer) use ($decoratedLoop): void {
            self::assertInstanceOf(TimerInterface::class, $timer);

            $decoratedLoop->cancelTimer($timer);
        };

        self::assertStringNotContainsString('react_event_loop_timer_ticks_total', $registry->print(new Prometheus()));
        self::assertStringNotContainsString('react_event_loop_timers', $registry->print(new Prometheus()));

        $decoratedLoop->addPeriodicTimer($interval, $listener);

        self::assertStringNotContainsString('react_event_loop_timer_ticks_total', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_timers{kind="periodic"} 1', $registry->print(new Prometheus()));

        $decoratedLoop->run();

        self::assertStringContainsString('react_event_loop_timer_ticks_total{kind="periodic"} 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_timers{kind="periodic"} 0', $registry->print(new Prometheus()));
    }

    /** @test */
    public function futureTick(): void
    {
        $listener = static function (): void {
        };

        $loop = Mockery::mock(LoopInterface::class);
        $loop->shouldReceive('futureTick')->withArgs(static function (callable $listener) use ($loop): bool {
            $listener($loop);

            return true;
        });

        $registry      = new Registry(Configuration::create());
        $decoratedLoop = new LoopDecorator($loop, $registry);

        self::assertStringNotContainsString('react_event_loop_future_tick_ticks_total', $registry->print(new Prometheus()));
        self::assertStringNotContainsString('react_event_loop_future_ticks', $registry->print(new Prometheus()));

        $decoratedLoop->futureTick($listener);

        self::assertStringContainsString('react_event_loop_future_tick_ticks_total 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_future_ticks 0', $registry->print(new Prometheus()));
    }

    /** @test */
    public function signal(): void
    {
        $func = function (int $signal): void {
            self::assertNotSame($this->signal, $signal);
        };

        $loop = Mockery::mock(LoopInterface::class);
        $loop->shouldReceive('addSignal')->withArgs(static function (int $signal, callable $listener): bool {
            if ($signal !== SIGINT) {
                return false;
            }

            $listener($signal);

            return true;
        });
        $loop->shouldReceive('removeSignal')->with(SIGINT, Mockery::type('callable'));

        $registry      = new Registry(Configuration::create());
        $decoratedLoop = new LoopDecorator($loop, $registry);

        self::assertStringNotContainsString('react_event_loop_signal_ticks_total', $registry->print(new Prometheus()));
        self::assertStringNotContainsString('react_event_loop_signals', $registry->print(new Prometheus()));

        $decoratedLoop->addSignal(SIGINT, $func);

        self::assertStringContainsString('react_event_loop_signal_ticks_total{signal="2"} 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_signals{signal="2"} 1', $registry->print(new Prometheus()));

        $decoratedLoop->removeSignal(SIGINT, $func);

        self::assertStringContainsString('react_event_loop_signal_ticks_total{signal="2"} 1', $registry->print(new Prometheus()));
        self::assertStringContainsString('react_event_loop_signals{signal="2"} 0', $registry->print(new Prometheus()));
    }
}
