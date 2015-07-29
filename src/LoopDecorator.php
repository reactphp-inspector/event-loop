<?php

namespace WyriHaximus\React\Inspector;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class LoopDecorator implements LoopInterface
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var array
     */
    protected $timers = [];

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Register a listener to be notified when a stream is ready to read.
     *
     * @param stream $stream The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addReadStream($stream, callable $listener)
    {
        $timerData = [
            'type' => 'stream_read',
            'hash' => spl_object_hash($stream),
            'run' => [],
        ];
        $timerData['timer'] = Timer::start();
        $this->loop->addReadStream($stream, function ($stream, $loop) use ($listener, &$timerData) {
            $run = [];
            $run['timer'] = Timer::start();
            $listener($stream, $loop);
            $run['timer']->stop();
            $timerData['run'][] = $run;
        });
        $timerData['timer']->stop();
        $this->timers[] = &$timerData;
    }

    /**
     * Register a listener to be notified when a stream is ready to write.
     *
     * @param stream $stream The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addWriteStream($stream, callable $listener)
    {
        $timerData = [
            'type' => 'stream_write',
            'hash' => spl_object_hash($stream),
            'run' => [],
        ];
        $timerData['timer'] = Timer::start();
        $this->loop->addReadStream($stream, function ($stream, $loop) use ($listener, &$timerData) {
            $run = [];
            $run['timer'] = Timer::start();
            $listener($stream, $loop);
            $run['timer']->stop();
            $timerData['run'][] = $run;
        });
        $timerData['timer']->stop();
        $this->timers[] = &$timerData;
    }

    /**
     * Remove the read event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeReadStream($stream)
    {
        $this->loop->removeReadStream($stream);
    }

    /**
     * Remove the write event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeWriteStream($stream)
    {
        $this->loop->removeWriteStream($stream);
    }

    /**
     * Remove all listeners for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeStream($stream)
    {
        $this->loop->removeStream($stream);
    }

    /**
     * Enqueue a callback to be invoked once after the given interval.
     *
     * The execution order of timers scheduled to execute at the same time is
     * not guaranteed.
     *
     * @param numeric $interval The number of seconds to wait before execution.
     * @param callable $callback The callback to invoke.
     *
     * @return TimerInterface
     */
    public function addTimer($interval, callable $callback)
    {
        $timerData = [
            'type' => 'timer',
        ];
        $timerData['timer'] = Timer::start();
        $loopTimer = $this->loop->addTimer($interval, function (TimerInterface $loopTimer) use ($callback, &$timerData) {
            $run = [
                'hash' => spl_object_hash($loopTimer),
            ];
            $run['timer'] = Timer::start();
            $callback($loopTimer);
            $run['timer']->stop();
            $timerData['run'] = $run;
        });
        $timerData['timer']->stop();
        $this->timers[] = &$timerData;
        return $loopTimer;
    }

    /**
     * Enqueue a callback to be invoked repeatedly after the given interval.
     *
     * The execution order of timers scheduled to execute at the same time is
     * not guaranteed.
     *
     * @param numeric $interval The number of seconds to wait before execution.
     * @param callable $callback The callback to invoke.
     *
     * @return TimerInterface
     */
    public function addPeriodicTimer($interval, callable $callback)
    {
        $timerData = [
            'type' => 'timer_periodic',
            'run' => [],
        ];
        $timerData['timer'] = Timer::start();
        $loopTimer = $this->loop->addPeriodicTimer($interval, function (TimerInterface $loopTimer) use ($callback, &$timerData) {
            $run = [
                'hash' => spl_object_hash($loopTimer),
            ];
            $run['timer'] = Timer::start();
            $callback($loopTimer);
            $run['timer']->stop();
            $timerData['run'][] = $run;
        });
        $timerData['timer']->stop();
        $this->timers[] = &$timerData;
        return $loopTimer;
    }

    /**
     * Cancel a pending timer.
     *
     * @param TimerInterface $timer The timer to cancel.
     */
    public function cancelTimer(TimerInterface $timer)
    {
        return $this->loop->cancelTimer($timer);
    }

    /**
     * Check if a given timer is active.
     *
     * @param TimerInterface $timer The timer to check.
     *
     * @return boolean True if the timer is still enqueued for execution.
     */
    public function isTimerActive(TimerInterface $timer)
    {
        return $this->loop->isTimerActive($timer);
    }

    /**
     * Schedule a callback to be invoked on the next tick of the event loop.
     *
     * Callbacks are guaranteed to be executed in the order they are enqueued,
     * before any timer or stream events.
     *
     * @param callable $listener The callback to invoke.
     */
    public function nextTick(callable $listener)
    {
        $timerData = [
            'type' => 'tick_next',
        ];
        $timerData['timer'] = Timer::start();
        $loopTimer = $this->loop->nextTick(function () use ($listener, &$timerData) {
            $timerData['run'] = [
                'hash' => spl_object_hash($listener),
            ];
            $timerData['run']['timer'] = Timer::start();
            $listener();
            $timerData['run']['timer']->stop();
        });
        $timerData['timer']->stop();
        $this->timers[] = &$timerData;
        return $loopTimer;
    }

    /**
     * Schedule a callback to be invoked on a future tick of the event loop.
     *
     * Callbacks are guaranteed to be executed in the order they are enqueued.
     *
     * @param callable $listener The callback to invoke.
     */
    public function futureTick(callable $listener)
    {
        $timerData = [
            'type' => 'tick_future',
        ];
        $timerData['timer'] = Timer::start();
        $loopTimer = $this->loop->futureTick(function () use ($listener, &$timerData) {
            $timerData['run'] = [
                'hash' => spl_object_hash($listener),
            ];
            $timerData['run']['timer'] = Timer::start();
            $listener();
            $timerData['run']['timer']->stop();
        });
        $timerData['timer']->stop();
        $this->timers[] = &$timerData;
        return $loopTimer;
    }

    /**
     * Perform a single iteration of the event loop.
     */
    public function tick()
    {
        $timer = Timer::start();
        $this->loop->tick();
        $timer->stop();
        $this->timers[] = [
            'type' => 'tick',
            'timer' => $timer,
        ];
    }

    /**
     * Run the event loop until there are no more tasks to perform.
     */
    public function run()
    {
        $timer = Timer::start();
        $this->loop->run();
        $timer->stop();
        $this->timers[] = [
            'type' => 'run',
            'timer' => $timer,
        ];
    }

    /**
     * Instruct a running event loop to stop.
     */
    public function stop()
    {
        $timer = Timer::start();
        $this->loop->stop();
        $timer->stop();
        $this->timers[] = [
            'type' => 'stop',
            'timer' => $timer,
        ];
    }

    public function getReccordings()
    {
        return $this->timers;
    }
}
