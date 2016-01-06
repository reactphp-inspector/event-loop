<?php

namespace WyriHaximus\React\Tests\Inspector;

use Phake;
use WyriHaximus\React\Inspector\LoopDecorator;

class LoopDecoratorTest extends \PHPUnit_Framework_TestCase
{
    public function testAddReadStream()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addReadStream' => false,
            'addReadStreamTick' => false,
        ];

        $stream = 'abc';
        $listener = function ($passedStream, $passedLoop) use (&$called, $stream, $loop) {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($loop, $passedLoop);
            $called['listener'] = true;
        };
        $decoratedLoop->on('addReadStream', function ($passedStream, $passedListener) use (&$called, $stream, $listener) {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($listener, $passedListener);
            $called['addReadStream'] = true;
        });
        $decoratedLoop->on('addReadStreamTick', function ($passedStream, $passedListener) use (&$called, $stream, $listener) {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($listener, $passedListener);
            $called['addReadStreamTick'] = true;
        });

        Phake::when($loop)->addReadStream($stream, $listener)->thenReturnCallback(function ($stream, $listener) use ($loop) {
            $listener($stream, $loop);
        });

        $decoratedLoop->addReadStream($stream, $listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->addReadStream($stream, $listener);
    }

    public function testAddWriteStream()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addWriteStream' => false,
            'addWriteStreamTick' => false,
        ];

        $stream = 'abc';
        $listener = function ($passedStream, $passedLoop) use (&$called, $stream, $loop) {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($loop, $passedLoop);
            $called['listener'] = true;
        };
        $decoratedLoop->on('addWriteStream', function ($passedStream, $passedListener) use (&$called, $stream, $listener) {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($listener, $passedListener);
            $called['addWriteStream'] = true;
        });
        $decoratedLoop->on('addWriteStreamTick', function ($passedStream, $passedListener) use (&$called, $stream, $listener) {
            $this->assertSame($stream, $passedStream);
            $this->assertSame($listener, $passedListener);
            $called['addWriteStreamTick'] = true;
        });

        Phake::when($loop)->addWriteStream($stream, $listener)->thenReturnCallback(function ($stream, $listener) use ($loop) {
            $listener($stream, $loop);
        });

        $decoratedLoop->addWriteStream($stream, $listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->addWriteStream($stream, $listener);
    }

    public function testRemoveReadStream()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $called = false;
        $stream = 'abc';
        $decoratedLoop->on('removeReadStream', function ($passedStream) use (&$called, $stream) {
            $this->assertSame($stream, $passedStream);
            $called = true;
        });

        $decoratedLoop->removeReadStream($stream);

        $this->assertTrue($called);
        Phake::verify($loop)->removeReadStream($stream);
    }

    public function testRemoveWriteStream()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $called = false;
        $stream = 'abc';
        $decoratedLoop->on('removeWriteStream', function ($passedStream) use (&$called, $stream) {
            $this->assertSame($stream, $passedStream);
            $called = true;
        });

        $decoratedLoop->removeWriteStream($stream);

        $this->assertTrue($called);
        Phake::verify($loop)->removeWriteStream($stream);
    }

    public function testRemoveStream()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $stream = 'abc';
        $decoratedLoop->removeStream($stream);

        Phake::verify($loop)->removeStream($stream);

    }

    public function testAddTimer()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $timer = Phake::mock('React\EventLoop\Timer\TimerInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addTimer' => false,
            'addTimerTick' => false,
        ];

        $interval = 123;
        $listener = function ($timer) use (&$called) {
            $this->assertInstanceOf('React\EventLoop\Timer\TimerInterface', $timer);
            $called['listener'] = true;
        };
        $decoratedLoop->on('addTimer', function ($passedInterval, $passedListener, $timer) use (&$called, $interval, $listener) {
            $this->assertSame($interval, $passedInterval);
            $this->assertSame($listener, $passedListener);
            $this->assertInstanceOf('React\EventLoop\Timer\TimerInterface', $timer);
            $called['addTimer'] = true;
        });
        $decoratedLoop->on('addTimerTick', function ($passedInterval, $passedListener, $timer) use (&$called, $interval, $listener) {
            $this->assertSame($interval, $passedInterval);
            $this->assertSame($listener, $passedListener);
            $this->assertInstanceOf('React\EventLoop\Timer\TimerInterface', $timer);
            $called['addTimerTick'] = true;
        });

        Phake::when($loop)->addTimer($interval, $listener)->thenReturnCallback(function ($stream, $listener) use ($timer) {
            $listener($timer);
            return $timer;
        });

        $decoratedLoop->addTimer($interval, $listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->addTimer($interval, $listener);
    }

    public function testAddPeriodicTimer()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $timer = Phake::mock('React\EventLoop\Timer\TimerInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'addPeriodicTimer' => false,
            'addPeriodicTimerTick' => false,
        ];

        $interval = 123;
        $listener = function ($timer) use (&$called) {
            $this->assertInstanceOf('React\EventLoop\Timer\TimerInterface', $timer);
            $called['listener'] = true;
        };
        $decoratedLoop->on('addPeriodicTimer', function ($passedInterval, $passedListener, $passedTimer) use (&$called, $interval, $listener, $timer) {
            $this->assertSame($interval, $passedInterval);
            $this->assertSame($listener, $passedListener);
            $this->assertSame($timer, $passedTimer);
            $called['addPeriodicTimer'] = true;
        });
        $decoratedLoop->on('addPeriodicTimerTick', function ($passedInterval, $passedListener, $passedTimer) use (&$called, $interval, $listener, $timer) {
            $this->assertSame($interval, $passedInterval);
            $this->assertSame($listener, $passedListener);
            $this->assertSame($timer, $passedTimer);
            $called['addPeriodicTimerTick'] = true;
        });

        Phake::when($loop)->addPeriodicTimer($interval, $listener)->thenReturnCallback(function ($stream, $listener) use ($timer) {
            $listener($timer);
            return $timer;
        });

        $decoratedLoop->addPeriodicTimer($interval, $listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->addPeriodicTimer($interval, $listener);
    }

    public function testCancelTimer()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $timer = Phake::mock('React\EventLoop\Timer\TimerInterface');

        $decoratedLoop = new LoopDecorator($loop);

        $called = false;
        $decoratedLoop->on('cancelTimer', function ($passedTimer) use (&$called, $timer) {
            $this->assertSame($timer, $passedTimer);
            $called = true;
        });

        $decoratedLoop->cancelTimer($timer);

        $this->assertTrue($called);
        Phake::verify($loop)->cancelTimer($timer);
    }

    public function testIsTimerActive()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $timer = Phake::mock('React\EventLoop\Timer\TimerInterface');
        $decoratedLoop->isTimerActive($timer);

        Phake::verify($loop)->isTimerActive($timer);
    }

    public function testNextTick()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'nextTick' => false,
            'nextTickTick' => false,
        ];

        $listener = function () use (&$called) {
            $called['listener'] = true;
        };
        $decoratedLoop->on('nextTick', function ($passedListener) use (&$called, $listener) {
            $this->assertSame($listener, $passedListener);
            $called['nextTick'] = true;
        });
        $decoratedLoop->on('nextTickTick', function ($passedListener) use (&$called, $listener) {
            $this->assertSame($listener, $passedListener);
            $called['nextTickTick'] = true;
        });

        Phake::when($loop)->nextTick($listener)->thenReturnCallback(function ($listener) {
            $listener();
        });

        $decoratedLoop->nextTick($listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->nextTick($listener);
    }

    public function testFutureTick()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'listener' => false,
            'futureTick' => false,
            'futureTickTick' => false,
        ];

        $listener = function () use (&$called) {
            $called['listener'] = true;
        };
        $decoratedLoop->on('futureTick', function ($passedListener) use (&$called, $listener) {
            $this->assertSame($listener, $passedListener);
            $called['futureTick'] = true;
        });
        $decoratedLoop->on('futureTickTick', function ($passedListener) use (&$called, $listener) {
            $this->assertSame($listener, $passedListener);
            $called['futureTickTick'] = true;
        });

        Phake::when($loop)->futureTick($listener)->thenReturnCallback(function ($listener) {
            $listener();
        });

        $decoratedLoop->futureTick($listener);

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->futureTick($listener);
    }

    public function testTick()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');

        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'tickStart' => false,
            'tickDone' => false,
        ];

        foreach ($called as $key => $call) {
            $eventKey = $key;
            $decoratedLoop->on($eventKey, function () use (&$called, $eventKey) {
                $called[$eventKey]= true;
            });
        }

        $decoratedLoop->tick();

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->tick();
    }

    public function testRun()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');

        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'runStart' => false,
            'runDone' => false,
        ];

        foreach ($called as $key => $call) {
            $eventKey = $key;
            $decoratedLoop->on($eventKey, function () use (&$called, $eventKey) {
                $called[$eventKey]= true;
            });
        }

        $decoratedLoop->run();

        foreach ($called as $key => $call) {
            $this->assertTrue($call, $key);
        }

        Phake::verify($loop)->run();
    }

    public function testStop()
    {
        $loop = Phake::mock('React\EventLoop\LoopInterface');

        $decoratedLoop = new LoopDecorator($loop);

        $called = [
            'stopStart' => false,
            'stopDone' => false,
        ];

        foreach ($called as $key => $call) {
            $eventKey = $key;
            $decoratedLoop->on($eventKey, function () use (&$called, $eventKey) {
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