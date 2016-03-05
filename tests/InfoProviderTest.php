<?php

namespace WyriHaximus\React\Tests\Inspector;

use Phake;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\EventLoop\Timer\TimerInterface;
use WyriHaximus\React\Inspector\InfoProvider;
use WyriHaximus\React\Inspector\LoopDecorator;

class InfoProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var InfoProvider
     */
    protected $infoProvider;

    public function setUp()
    {
        parent::setUp();
        $this->loop = new LoopDecorator(new StreamSelectLoop());
        $this->infoProvider = new InfoProvider($this->loop);
    }

    public function tearDown()
    {
        $this->infoProvider = null;
        $this->loop = null;
        parent::tearDown();
    }

    public function testResetTotals()
    {
        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['ticks']['future']['total']);

        $this->loop->futureTick(function () {});

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(1, $counters['ticks']['future']['total']);

        $this->loop->run();

        $this->infoProvider->resetTotals();

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['ticks']['future']['total']);
    }

    public function testResetTicks()
    {
        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['ticks']['future']['ticks']);

        $this->loop->futureTick(function () {});
        $this->loop->run();

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(1, $counters['ticks']['future']['ticks']);

        $this->infoProvider->resetTicks();

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['ticks']['future']['ticks']);
    }

    public function testFutureTick()
    {
        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['ticks']['future']['current']);
        $this->assertSame(0, $counters['ticks']['future']['total']);
        $this->assertSame(0, $counters['ticks']['future']['ticks']);

        $this->loop->futureTick(function () {});

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(1, $counters['ticks']['future']['current']);
        $this->assertSame(1, $counters['ticks']['future']['total']);
        $this->assertSame(0, $counters['ticks']['future']['ticks']);

        $this->loop->run();

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['ticks']['future']['current']);
        $this->assertSame(1, $counters['ticks']['future']['total']);
        $this->assertSame(1, $counters['ticks']['future']['ticks']);
    }

    public function testNextTick()
    {
        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['ticks']['next']['current']);
        $this->assertSame(0, $counters['ticks']['next']['total']);
        $this->assertSame(0, $counters['ticks']['next']['ticks']);

        $this->loop->nextTick(function () {});

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(1, $counters['ticks']['next']['current']);
        $this->assertSame(1, $counters['ticks']['next']['total']);
        $this->assertSame(0, $counters['ticks']['next']['ticks']);

        $this->loop->run();

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['ticks']['next']['current']);
        $this->assertSame(1, $counters['ticks']['next']['total']);
        $this->assertSame(1, $counters['ticks']['next']['ticks']);
    }

    public function testTimer()
    {
        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['timers']['once']['current']);
        $this->assertSame(0, $counters['timers']['once']['total']);
        $this->assertSame(0, $counters['timers']['once']['ticks']);

        $this->loop->addTimer(0.0001, function () {});

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(1, $counters['timers']['once']['current']);
        $this->assertSame(1, $counters['timers']['once']['total']);
        $this->assertSame(0, $counters['timers']['once']['ticks']);

        $this->loop->run();

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['timers']['once']['current']);
        $this->assertSame(1, $counters['timers']['once']['total']);
        $this->assertSame(1, $counters['timers']['once']['ticks']);
    }

    public function testPeriodicTimer()
    {
        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['timers']['periodic']['current']);
        $this->assertSame(0, $counters['timers']['periodic']['total']);
        $this->assertSame(0, $counters['timers']['periodic']['ticks']);

        $i = 1;
        $this->loop->addPeriodicTimer(0.0001, function (TimerInterface $timer) use (&$i) {
            if ($i === 3) {
                $timer->cancel();
            }
            $i++;
        });

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(1, $counters['timers']['periodic']['current']);
        $this->assertSame(1, $counters['timers']['periodic']['total']);
        $this->assertSame(0, $counters['timers']['periodic']['ticks']);

        $this->loop->run();

        $counters = $this->infoProvider->getCounters();
        $this->assertSame(0, $counters['timers']['periodic']['current']);
        $this->assertSame(1, $counters['timers']['periodic']['total']);
        $this->assertSame(3, $counters['timers']['periodic']['ticks']);
    }
}
