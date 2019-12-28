<?php declare(strict_types=1);

namespace ReactInspector\EventLoop\Tests;

use Phake;
use Prophecy\Argument;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use ReactInspector\EventLoop\LoopDecorator;

/**
 * @internal
 */
class LoopDecoratorTest extends AsyncTestCase
{
    public function testAddReadStream(): void
    {
        $loop = Phake::mock(LoopInterface::class);
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addReadStream' => false,
            'readStreamTick' => false,
        ];

        $stream = 'abc';
        $listener = function ($passedStream, $passedLoop) use (&$called, $stream, $decoratedLoop): void {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($decoratedLoop, $passedLoop);
            $called['listener'] = true;
        };
        $decoratedLoop->on('addReadStream', function ($passedStream, $passedListener) use (&$called, $stream, $listener): void {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($listener, $passedListener);
            $called['addReadStream'] = true;
        });
        $decoratedLoop->on('readStreamTick', function ($passedStream, $passedListener) use (&$called, $stream, $listener): void {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($listener, $passedListener);
            $called['readStreamTick'] = true;
        });

        Phake::when($loop)->addReadStream($stream, $listener)->thenReturnCallback(function ($stream, $listener) use ($loop): void {
            $listener($stream, $loop);
        });

        $decoratedLoop->addReadStream($stream, $listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->addReadStream($stream, $listener);
    }

    public function testAddWriteStream(): void
    {
        $loop = Phake::mock(LoopInterface::class);
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addWriteStream' => false,
            'writeStreamTick' => false,
        ];

        $stream = 'abc';
        $listener = function ($passedStream, $passedLoop) use (&$called, $stream, $decoratedLoop): void {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($decoratedLoop, $passedLoop);
            $called['listener'] = true;
        };
        $decoratedLoop->on('addWriteStream', function ($passedStream, $passedListener) use (&$called, $stream, $listener): void {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($listener, $passedListener);
            $called['addWriteStream'] = true;
        });
        $decoratedLoop->on('writeStreamTick', function ($passedStream, $passedListener) use (&$called, $stream, $listener): void {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($listener, $passedListener);
            $called['writeStreamTick'] = true;
        });

        Phake::when($loop)->addWriteStream($stream, $listener)->thenReturnCallback(function ($stream, $listener) use ($loop): void {
            $listener($stream, $loop);
        });

        $decoratedLoop->addWriteStream($stream, $listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->addWriteStream($stream, $listener);
    }

    public function testRemoveReadStream(): void
    {
        $loop = Phake::mock(LoopInterface::class);
        $decoratedLoop = new LoopDecorator($loop);

        $called = false;
        $stream = 'abc';
        $decoratedLoop->on('removeReadStream', function ($passedStream) use (&$called, $stream): void {
            $this->assertSame($stream, $passedStream);
            $called = true;
        });

        $decoratedLoop->removeReadStream($stream);

        $this->assertTrue($called);
        Phake::verify($loop)->removeReadStream($stream);
    }

    public function testRemoveWriteStream(): void
    {
        $loop = Phake::mock(LoopInterface::class);
        $decoratedLoop = new LoopDecorator($loop);

        $called = false;
        $stream = 'abc';
        $decoratedLoop->on('removeWriteStream', function ($passedStream) use (&$called, $stream): void {
            $this->assertSame($stream, $passedStream);
            $called = true;
        });

        $decoratedLoop->removeWriteStream($stream);

        $this->assertTrue($called);
        Phake::verify($loop)->removeWriteStream($stream);
    }

    public function testAddTimer(): void
    {
        $loop = Factory::create();
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addTimer' => false,
            'timerTick' => false,
        ];

        $interval = 0.123;
        $listener = function ($timer) use (&$called): void {
            $this->assertInstanceOf(TimerInterface::class, $timer);
            $called['listener'] = true;
        };
        $decoratedLoop->on('addTimer', function ($passedInterval, $passedListener, $timer) use (&$called, $interval, $listener): void {
            $this->assertSame($interval, $passedInterval);
            $this->assertSame($listener, $passedListener);
            $this->assertInstanceOf(TimerInterface::class, $timer);
            $called['addTimer'] = true;
        });
        $decoratedLoop->on('timerTick', function ($passedInterval, $passedListener, $timer) use (&$called, $interval, $listener): void {
            $this->assertSame($interval, $passedInterval);
            $this->assertSame($listener, $passedListener);
            $this->assertInstanceOf(TimerInterface::class, $timer);
            $called['timerTick'] = true;
        });

        $decoratedLoop->addTimer($interval, $listener);

        $decoratedLoop->run();

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }
    }

    public function testAddPeriodicTimer(): void
    {
        $loop = Factory::create();
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addPeriodicTimer' => false,
            'periodicTimerTick' => false,
        ];

        $interval = 0.123;
        $listener = function ($timer) use (&$called, $loop): void {
            $this->assertInstanceOf(TimerInterface::class, $timer);
            $called['listener'] = true;
            $loop->cancelTimer($timer);
        };
        $decoratedLoop->on('addPeriodicTimer', function ($passedInterval, $passedListener, $passedTimer) use (&$called, $interval, $listener): void {
            $this->assertSame($interval, $passedInterval);
            $this->assertSame($listener, $passedListener);
            $this->assertInstanceOf(TimerInterface::class, $passedTimer);
            $called['addPeriodicTimer'] = true;
        });
        $decoratedLoop->on('periodicTimerTick', function ($passedInterval, $passedListener, $passedTimer) use (&$called, $interval, $listener): void {
            $this->assertSame($interval, $passedInterval);
            $this->assertSame($listener, $passedListener);
            $this->assertInstanceOf(TimerInterface::class, $passedTimer);
            $called['periodicTimerTick'] = true;
        });

        $decoratedLoop->addPeriodicTimer($interval, $listener);

        $decoratedLoop->run();

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }
    }

    public function testCancelTimer(): void
    {
        $loop = Phake::mock(LoopInterface::class);
        $timer = Phake::mock(TimerInterface::class);

        $decoratedLoop = new LoopDecorator($loop);

        $called = false;
        $decoratedLoop->on('cancelTimer', function ($passedTimer) use (&$called, $timer): void {
            $this->assertSame($timer, $passedTimer);
            $called = true;
        });

        $decoratedLoop->cancelTimer($timer);

        $this->assertTrue($called);
        Phake::verify($loop)->cancelTimer($timer);
    }

    public function testFutureTick(): void
    {
        $loop = Phake::mock(LoopInterface::class);
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'futureTick' => false,
            'futureTickTick' => false,
        ];

        $listener = function () use (&$called): void {
            $called['listener'] = true;
        };
        $decoratedLoop->on('futureTick', function ($passedListener) use (&$called, $listener): void {
            $this->assertSame($listener, $passedListener);
            $called['futureTick'] = true;
        });
        $decoratedLoop->on('futureTickTick', function ($passedListener) use (&$called, $listener): void {
            $this->assertSame($listener, $passedListener);
            $called['futureTickTick'] = true;
        });

        Phake::when($loop)->futureTick($listener)->thenReturnCallback(function ($listener) use ($loop): void {
            $listener($loop);
        });

        $decoratedLoop->futureTick($listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->futureTick($listener);
    }

    public function testSignal(): void
    {
        $func = function (int $signal): void {
        };

        $loop = $this->prophesize(LoopInterface::class);
        $loop->addSignal(\SIGINT, Argument::type('callable'))->shouldBeCalled()->will(function ($args): void {
            [$signal, $listener] = $args;
            $listener($signal);
        });
        $loop->removeSignal(\SIGINT, Argument::type('callable'))->shouldBeCalled();

        $decoratedLoop = new LoopDecorator($loop->reveal());
        $decoratedLoop->on('addSignal', $this->expectCallableOnce());
        $decoratedLoop->on('signalTick', $this->expectCallableOnce());
        $decoratedLoop->on('removeSignal', $this->expectCallableOnce());

        $decoratedLoop->addSignal(\SIGINT, $func);
        $decoratedLoop->removeSignal(\SIGINT, $func);
    }

    public function testRun(): void
    {
        $loop = Phake::mock(LoopInterface::class);

        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'runStart' => false,
            'runDone' => false,
        ];

        foreach ($called as $key => $call) {
            $eventKey = $key;
            $decoratedLoop->on($eventKey, function () use (&$called, $eventKey): void {
                $called[$eventKey]= true;
            });
        }

        $decoratedLoop->run();

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->run();
    }

    public function testStop(): void
    {
        $loop = Phake::mock(LoopInterface::class);

        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'stopStart' => false,
            'stopDone' => false,
        ];

        foreach ($called as $key => $call) {
            $eventKey = $key;
            $decoratedLoop->on($eventKey, function () use (&$called, $eventKey): void {
                $called[$eventKey]= true;
            });
        }

        $decoratedLoop->stop();

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->stop();
    }
}
