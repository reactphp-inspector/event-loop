<?php

declare(strict_types=1);

namespace ReactInspector\EventLoop;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Registry;
use WyriHaximus\Metrics\Registry\Counters;
use WyriHaximus\Metrics\Registry\Gauges;

use function spl_object_id;

/** @psalm-suppress UnusedVariable */
final class LoopDecorator implements LoopInterface
{
    /** @var array<int, array<int, callable>> */
    private array $signalListeners = [];

    private Gauges $streams;
    private Counters $streamTicks;
    private Gauges $timers;
    private Counters $timerTicks;
    private Gauges $futureTicks;
    private Counters $futureTickTicks;
    private Gauges $signals;
    private Counters $signalTicks;

    public function __construct(private LoopInterface $loop, private Registry $registry)
    {
        $this->streams         = $this->registry->gauge('react_event_loop_streams', 'Active streams', new Label\Name('kind'));
        $this->streamTicks     = $this->registry->counter('react_event_loop_stream_ticks', 'Stream calls occurred', new Label\Name('kind'));
        $this->timers          = $this->registry->gauge('react_event_loop_timers', 'Active timers', new Label\Name('kind'));
        $this->timerTicks      = $this->registry->counter('react_event_loop_timer_ticks', 'Timer calls occurred', new Label\Name('kind'));
        $this->futureTicks     = $this->registry->gauge('react_event_loop_future_ticks', 'Queued future ticks');
        $this->futureTickTicks = $this->registry->counter('react_event_loop_future_tick_ticks', 'Ticks calls occurred');
        $this->signals         = $this->registry->gauge('react_event_loop_signals', 'Active signal listeners', new Label\Name('signal'));
        $this->signalTicks     = $this->registry->counter('react_event_loop_signal_ticks', 'The number of calls occurred when a signal is caught', new Label\Name('signal'));
    }

    /**
     * {@inheritDoc}
     */
    public function addReadStream($stream, $listener): void
    {
        $this->streams->gauge(new Label('kind', 'read'))->incr();
        /** @psalm-suppress MissingClosureParamType */
        $this->loop->addReadStream($stream, function ($stream) use ($listener): void {
            $this->streamTicks->counter(new Label('kind', 'read'))->incr();
            $listener($stream, $this);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function addWriteStream($stream, $listener): void
    {
        $this->streams->gauge(new Label('kind', 'write'))->incr();
        /** @psalm-suppress MissingClosureParamType */
        $this->loop->addWriteStream($stream, function ($stream) use ($listener): void {
            $this->streamTicks->counter(new Label('kind', 'write'))->incr();
            $listener($stream, $this);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function removeReadStream($stream): void
    {
        $this->streams->gauge(new Label('kind', 'read'))->dcr();
        $this->loop->removeReadStream($stream);
    }

    /**
     * {@inheritDoc}
     */
    public function removeWriteStream($stream): void
    {
        $this->streams->gauge(new Label('kind', 'write'))->dcr();
        $this->loop->removeWriteStream($stream);
    }

    /**
     * {@inheritDoc}
     */
    // phpcs:disable
    public function addTimer($interval, $callback)
    {
        $loopTimer = null;
        $wrapper   = function () use (&$loopTimer, $callback): void {
            $this->timers->gauge(new Label('kind', 'one-off'))->dcr();
            $this->timerTicks->counter(new Label('kind', 'one-off'))->incr();
            $callback($loopTimer);
        };
        $this->timers->gauge(new Label('kind', 'one-off'))->incr();

        return $this->loop->addTimer($interval, $wrapper);
    }

    /**
     * {@inheritDoc}
     */
    // phpcs:disable
    public function addPeriodicTimer($interval, $callback)
    {
        $this->timers->gauge(new Label('kind', 'periodic'))->incr();

        /**
         * @psalm-suppress MixedAssignment
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress UndefinedVariable
         */
        return $loopTimer = $this->loop->addPeriodicTimer(
            $interval,
            function () use (&$loopTimer, $callback): void {
                $this->timerTicks->counter(new Label('kind', 'periodic'))->incr();
                $callback($loopTimer);
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function cancelTimer(TimerInterface $timer): void
    {
        $this->timers->gauge(new Label('kind', $timer->isPeriodic() ? 'periodic' : 'one-off'))->dcr();

        $this->loop->cancelTimer($timer);
    }

    /**
     * {@inheritDoc}
     */
    public function futureTick($listener): void
    {
        $this->futureTicks->gauge()->incr();

        $this->loop->futureTick(function () use ($listener): void {
            $this->futureTicks->gauge()->dcr();
            $this->futureTickTicks->counter()->incr();
            $listener($this);
        });
    }

    public function run(): void
    {
        $this->loop->run();
    }

    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
        $this->loop->stop();
    }

    /**
     * {@inheritDoc}
     */
    public function addSignal($signal, $listener): void
    {
        /**
         * @psalm-suppress InvalidArgument
         * @phpstan-ignore-next-line
         */
        $listenerId                                  = spl_object_id($listener);
        /** @psalm-suppress MissingClosureParamType */
        $wrapper                                     = function ($signal) use ($listener): void {
            $this->signalTicks->counter(new Label('signal', (string)$signal))->incr();
            $listener($signal);
        };
        $this->signalListeners[$signal][$listenerId] = $wrapper;
        $this->signals->gauge(new Label('signal', (string)$signal))->incr();
        $this->loop->addSignal($signal, $wrapper);
    }

    /**
     * {@inheritDoc}
     */
    public function removeSignal($signal, $listener): void
    {
        /**
         * @psalm-suppress InvalidArgument
         * @phpstan-ignore-next-line
         */
        $listenerId = spl_object_id($listener);
        $wrapper    = $this->signalListeners[$signal][$listenerId];
        unset($this->signalListeners[$signal][$listenerId]);
        $this->signals->gauge(new Label('signal', (string)$signal))->dcr();
        $this->loop->removeSignal($signal, $wrapper);
    }
}
