<?php

namespace WyriHaximus\React\Tests\Inspector;

use Phake;
use React\EventLoop\Timer\Timer;
use WyriHaximus\React\Inspector\LoopDecorator;
use WyriHaximus\React\Inspector\TimerDecorator;

class TimerDecoratorTest extends \PHPUnit_Framework_TestCase
{
    public function testTimerDecorator()
    {
        $func = function () {};

        $loop = Phake::mock('React\EventLoop\LoopInterface');

        $decoratedLoop = new LoopDecorator($loop);
        $timer = new Timer($decoratedLoop, 123, $func, true);
        $decoratedTimer = new TimerDecorator($decoratedLoop, $timer);

        Phake::when($loop)->isTimerActive($timer)->thenReturn(true);

        $this->assertSame($decoratedLoop, $decoratedTimer->getLoop());
        $this->assertSame(123.0, $decoratedTimer->getInterval());
        $this->assertSame($func, $decoratedTimer->getCallback());
        $this->assertSame(null, $decoratedTimer->getData());

        $data = 'sdkgn ihrngihnrigihrw';
        $decoratedTimer->setData($data);
        $this->assertSame($data, $decoratedTimer->getData());

        $this->assertSame(true, $decoratedTimer->isPeriodic());
        $this->assertSame(true, $decoratedTimer->isActive());

        $decoratedTimer->cancel();
        Phake::verify($loop)->cancelTimer($timer);
    }
}
