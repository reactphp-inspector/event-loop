<?php declare(strict_types=1);

namespace ReactInspector\Tests\EventLoop;

use Prophecy\Argument;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use ReactInspector\EventLoop\LoopDecorator;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use const SIGINT;

/** @internal */
final class LoopDecoratorTest extends AsyncTestCase
{
    private int $signal = -1;

    public function testAddReadStream(): void
    {
        $called = [
            'listener' => false,
            'addReadStream' => false,
            'readStreamTick' => false,
        ];
        $stream = 'abc';

        $loopProphecy  = $this->prophesize(LoopInterface::class);
        $loop          = $loopProphecy->reveal();
        $decoratedLoop = new LoopDecorator($loop);

        // phpcs:disable
        $listener = function ($passedStream, $passedLoop) use (&$called, $stream, $decoratedLoop): void {
            self::assertSame($stream, $passedStream);
            self::assertSame($decoratedLoop, $passedLoop);
            $called['listener'] = true;
        };

        $loopProphecy->addReadStream($stream, Argument::type('callable'))->shouldBeCalled()->will(function (array $args) use ($loop): void {
            [$stream, $listener] = $args;
            $listener($stream, $loop);
        });
        // phpcs:enable

        $decoratedLoop->on('addReadStream', static function ($passedStream, $passedListener) use (&$called, $stream, $listener): void {
            self::assertSame($stream, $passedStream);
            self::assertSame($listener, $passedListener);
            $called['addReadStream'] = true;
        });
        $decoratedLoop->on('readStreamTick', static function ($passedStream, $passedListener) use (&$called, $stream, $listener): void {
            self::assertSame($stream, $passedStream);
            self::assertSame($listener, $passedListener);
            $called['readStreamTick'] = true;
        });

        $decoratedLoop->addReadStream($stream, $listener);

        foreach ($called as $key => $call) {
            self::assertTrue($call, $key);
        }
    }

    public function testAddWriteStream(): void
    {
        $called = [
            'listener' => false,
            'addWriteStream' => false,
            'writeStreamTick' => false,
        ];
        $stream = 'abc';

        $loopProphecy  = $this->prophesize(LoopInterface::class);
        $loop          = $loopProphecy->reveal();
        $decoratedLoop = new LoopDecorator($loop);

        // phpcs:disable
        $listener = function ($passedStream, $passedLoop) use (&$called, $stream, $decoratedLoop): void {
            self::assertSame($stream, $passedStream);
            self::assertSame($decoratedLoop, $passedLoop);
            $called['listener'] = true;
        };

        $loopProphecy->addWriteStream($stream, Argument::type('callable'))->shouldBeCalled()->will(function (array $args) use ($loop): void {
            [$stream, $listener] = $args;
            $listener($stream, $loop);
        });
        // phpcs:enable

        $decoratedLoop->on('addWriteStream', static function ($passedStream, $passedListener) use (&$called, $stream, $listener): void {
            self::assertSame($stream, $passedStream);
            self::assertSame($listener, $passedListener);
            $called['addWriteStream'] = true;
        });
        $decoratedLoop->on('writeStreamTick', static function ($passedStream, $passedListener) use (&$called, $stream, $listener): void {
            self::assertSame($stream, $passedStream);
            self::assertSame($listener, $passedListener);
            $called['writeStreamTick'] = true;
        });

        $decoratedLoop->addWriteStream($stream, $listener);

        foreach ($called as $key => $call) {
            self::assertTrue($call, $key);
        }
    }

    public function testRemoveReadStream(): void
    {
        $called = false;
        $stream = 'abc';

        $loopProphecy = $this->prophesize(LoopInterface::class);
        $loopProphecy->removeReadStream($stream)->shouldBeCalled();
        $loop          = $loopProphecy->reveal();
        $decoratedLoop = new LoopDecorator($loop);

        $decoratedLoop->on('removeReadStream', static function ($passedStream) use (&$called, $stream): void {
            self::assertSame($stream, $passedStream);
            $called = true;
        });

        $decoratedLoop->removeReadStream($stream);

        self::assertTrue($called);
    }

    public function testRemoveWriteStream(): void
    {
        $called = false;
        $stream = 'abc';

        $loopProphecy = $this->prophesize(LoopInterface::class);
        $loopProphecy->removeWriteStream($stream)->shouldBeCalled();
        $loop          = $loopProphecy->reveal();
        $decoratedLoop = new LoopDecorator($loop);

        $decoratedLoop->on('removeWriteStream', static function ($passedStream) use (&$called, $stream): void {
            self::assertSame($stream, $passedStream);
            $called = true;
        });

        $decoratedLoop->removeWriteStream($stream);

        self::assertTrue($called);
    }

    public function testAddTimer(): void
    {
        $loop          = Factory::create();
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addTimer' => false,
            'timerTick' => false,
        ];

        $interval = 0.123;
        $listener = static function ($timer) use (&$called): void {
            self::assertInstanceOf(TimerInterface::class, $timer);
            $called['listener'] = true;
        };
        $decoratedLoop->on('addTimer', static function ($passedInterval, $passedListener, $timer) use (&$called, $interval, $listener): void {
            self::assertSame($interval, $passedInterval);
            self::assertSame($listener, $passedListener);
            self::assertInstanceOf(TimerInterface::class, $timer);
            $called['addTimer'] = true;
        });
        $decoratedLoop->on('timerTick', static function ($passedInterval, $passedListener, $timer) use (&$called, $interval, $listener): void {
            self::assertSame($interval, $passedInterval);
            self::assertSame($listener, $passedListener);
            self::assertInstanceOf(TimerInterface::class, $timer);
            $called['timerTick'] = true;
        });

        $decoratedLoop->addTimer($interval, $listener);

        $decoratedLoop->run();

        foreach ($called as $key => $call) {
            self::assertTrue($call, $key);
        }
    }

    public function testAddPeriodicTimer(): void
    {
        $loop          = Factory::create();
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addPeriodicTimer' => false,
            'periodicTimerTick' => false,
        ];

        $interval = 0.123;
        $listener = static function ($timer) use (&$called, $loop): void {
            self::assertInstanceOf(TimerInterface::class, $timer);
            $called['listener'] = true;
            $loop->cancelTimer($timer);
        };
        $decoratedLoop->on('addPeriodicTimer', static function ($passedInterval, $passedListener, $passedTimer) use (&$called, $interval, $listener): void {
            self::assertSame($interval, $passedInterval);
            self::assertSame($listener, $passedListener);
            self::assertInstanceOf(TimerInterface::class, $passedTimer);
            $called['addPeriodicTimer'] = true;
        });
        $decoratedLoop->on('periodicTimerTick', static function ($passedInterval, $passedListener, $passedTimer) use (&$called, $interval, $listener): void {
            self::assertSame($interval, $passedInterval);
            self::assertSame($listener, $passedListener);
            self::assertInstanceOf(TimerInterface::class, $passedTimer);
            $called['periodicTimerTick'] = true;
        });

        $decoratedLoop->addPeriodicTimer($interval, $listener);

        $decoratedLoop->run();

        foreach ($called as $key => $call) {
            self::assertTrue($call, $key);
        }
    }

    public function testCancelTimer(): void
    {
        $timer = $this->prophesize(TimerInterface::class)->reveal();

        $loop = $this->prophesize(LoopInterface::class);
        $loop->cancelTimer($timer)->shouldBeCalled();

        $decoratedLoop = new LoopDecorator($loop->reveal());

        $called = false;
        $decoratedLoop->on('cancelTimer', static function ($passedTimer) use (&$called, $timer): void {
            self::assertSame($timer, $passedTimer);
            $called = true;
        });

        $decoratedLoop->cancelTimer($timer);

        self::assertTrue($called);
    }

    public function testFutureTick(): void
    {
        $loop = $this->prophesize(LoopInterface::class);

        // phpcs:disable
        $listener = function () use (&$called): void {
            $called['listener'] = true;
        };

        $loop->futureTick(Argument::type('callable'))->shouldBeCalled()->will(function ($args) use ($loop): void {
            [$listener] = $args;
            $listener($loop);
        });
        // phpcs:enable

        $decoratedLoop = new LoopDecorator($loop->reveal());

        $called = [
            'listener' => false,
            'futureTick' => false,
            'futureTickTick' => false,
        ];

        $decoratedLoop->on('futureTick', static function ($passedListener) use (&$called, $listener): void {
            self::assertSame($listener, $passedListener);
            $called['futureTick'] = true;
        });
        $decoratedLoop->on('futureTickTick', static function ($passedListener) use (&$called, $listener): void {
            self::assertSame($listener, $passedListener);
            $called['futureTickTick'] = true;
        });

        $decoratedLoop->futureTick($listener);

        foreach ($called as $key => $call) {
            self::assertTrue($call, $key);
        }
    }

    public function testSignal(): void
    {
        $func = function (int $signal): void {
            self::assertNotSame($this->signal, $signal);
        };

        $loop = $this->prophesize(LoopInterface::class);
        // phpcs:disable
        $loop->addSignal(SIGINT, Argument::type('callable'))->shouldBeCalled()->will(function ($args): void {
            [$signal, $listener] = $args;
            $listener($signal);
        });
        // phpcs:enable
        $loop->removeSignal(SIGINT, Argument::type('callable'))->shouldBeCalled();

        $decoratedLoop = new LoopDecorator($loop->reveal());
        $decoratedLoop->on('addSignal', $this->expectCallableOnce());
        $decoratedLoop->on('signalTick', $this->expectCallableOnce());
        $decoratedLoop->on('removeSignal', $this->expectCallableOnce());

        $decoratedLoop->addSignal(SIGINT, $func);
        $decoratedLoop->removeSignal(SIGINT, $func);
    }

    public function testRun(): void
    {
        $loop = $this->prophesize(LoopInterface::class);
        $loop->run()->shouldBeCalled();

        $decoratedLoop = new LoopDecorator($loop->reveal());

        $called = [
            'runStart' => false,
            'runDone' => false,
        ];

        foreach ($called as $key => $call) {
            $eventKey = $key;
            $decoratedLoop->on($eventKey, static function () use (&$called, $eventKey): void {
                $called[$eventKey] = true;
            });
        }

        $decoratedLoop->run();

        foreach ($called as $key => $call) {
            self::assertTrue($call, $key);
        }
    }

    public function testStop(): void
    {
        $loop = $this->prophesize(LoopInterface::class);
        $loop->stop()->shouldBeCalled();

        $decoratedLoop = new LoopDecorator($loop->reveal());

        $called = [
            'stopStart' => false,
            'stopDone' => false,
        ];

        foreach ($called as $key => $call) {
            $eventKey = $key;
            $decoratedLoop->on($eventKey, static function () use (&$called, $eventKey): void {
                $called[$eventKey] = true;
            });
        }

        $decoratedLoop->stop();

        foreach ($called as $key => $call) {
            self::assertTrue($call, $key);
        }
    }
}
