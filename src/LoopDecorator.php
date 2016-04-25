<?php

namespace WyriHaximus\React\Inspector;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class LoopDecorator implements LoopInterface, EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var LoopInterface
     */
    protected $loop;

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
        $this->emit('addReadStream', [$stream, $listener]);
        $this->loop->addReadStream($stream, function ($stream, $loop) use ($listener) {
            $this->emit('readStreamTick', [$stream, $listener]);
            $listener($stream, $loop);
        });
    }

    /**
     * Register a listener to be notified when a stream is ready to write.
     *
     * @param stream $stream The PHP stream resource to check.
     * @param callable $listener Invoked when the stream is ready.
     */
    public function addWriteStream($stream, callable $listener)
    {
        $this->emit('addWriteStream', [$stream, $listener]);
        $this->loop->addWriteStream($stream, function ($stream, $loop) use ($listener) {
            $this->emit('writeStreamTick', [$stream, $listener]);
            $listener($stream, $loop);
        });
    }

    /**
     * Remove the read event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeReadStream($stream)
    {
        $this->emit('removeReadStream', [$stream]);
        $this->loop->removeReadStream($stream);
    }

    /**
     * Remove the write event listener for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeWriteStream($stream)
    {
        $this->emit('removeWriteStream', [$stream]);
        $this->loop->removeWriteStream($stream);
    }

    /**
     * Remove all listeners for the given stream.
     *
     * @param stream $stream The PHP stream resource.
     */
    public function removeStream($stream)
    {
        $this->emit('removeStream', [$stream]);
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
        $loopTimer = null;
        $wrapper = function () use (&$loopTimer, $callback, $interval) {
            $this->emit('timerTick', [$interval, $callback, $loopTimer]);
            $callback($loopTimer);
        };
        $loopTimer = new TimerDecorator(
            $this,
            $this->loop->addTimer(
                $interval,
                $wrapper
            )
        );
        $this->emit('addTimer', [$interval, $callback, $loopTimer]);
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
        $loopTimer = new TimerDecorator(
            $this,
            $this->loop->addPeriodicTimer(
                $interval,
                function () use (&$loopTimer, $callback, $interval) {
                    $this->emit('periodicTimerTick', [$interval, $callback, $loopTimer]);
                    $callback($loopTimer);
                }
            )
        );
        $this->emit('addPeriodicTimer', [$interval, $callback, $loopTimer]);
        return $loopTimer;
    }

    /**
     * Cancel a pending timer.
     *
     * @param TimerInterface $timer The timer to cancel.
     */
    public function cancelTimer(TimerInterface $timer)
    {
        $this->emit('cancelTimer', [$timer]);
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
        $this->emit('nextTick', [$listener]);
        return $this->loop->nextTick(function (LoopInterface $loop) use ($listener) {
            $this->emit('nextTickTick', [$listener]);
            $listener($this);
        });
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
        $this->emit('futureTick', [$listener]);
        return $this->loop->futureTick(function (LoopInterface $loop) use ($listener) {
            $this->emit('futureTickTick', [$listener]);
            $listener($this);
        });
    }

    /**
     * Perform a single iteration of the event loop.
     */
    public function tick()
    {
        $this->emit('tickStart');
        $this->loop->tick();
        $this->emit('tickDone');
    }

    /**
     * Run the event loop until there are no more tasks to perform.
     */
    public function run()
    {
        $this->emit('runStart');
        $this->loop->run();
        $this->emit('runDone');
    }

    /**
     * Instruct a running event loop to stop.
     */
    public function stop()
    {
        $this->emit('stopStart');
        $this->loop->stop();
        $this->emit('stopDone');
    }
}
