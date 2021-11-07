<?php

declare(strict_types=1);

namespace ReactInspector\EventLoop;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

use function spl_object_hash;

final class LoopDecorator implements LoopInterface, EventEmitterInterface
{
    use EventEmitterTrait;

    private LoopInterface $loop;

    /** @var array<int, array<string, callable>> */
    private array $signalListeners = [];

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * {@inheritDoc}
     */
    public function addReadStream($stream, $listener): void
    {
        $this->emit('addReadStream', [$stream, $listener]);
        /** @psalm-suppress MissingClosureParamType */
        $this->loop->addReadStream($stream, function ($stream) use ($listener): void {
            $this->emit('readStreamTick', [$stream, $listener]);
            $listener($stream, $this);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function addWriteStream($stream, $listener): void
    {
        $this->emit('addWriteStream', [$stream, $listener]);
        /** @psalm-suppress MissingClosureParamType */
        $this->loop->addWriteStream($stream, function ($stream) use ($listener): void {
            $this->emit('writeStreamTick', [$stream, $listener]);
            $listener($stream, $this);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function removeReadStream($stream): void
    {
        $this->emit('removeReadStream', [$stream]);
        $this->loop->removeReadStream($stream);
    }

    /**
     * {@inheritDoc}
     */
    public function removeWriteStream($stream): void
    {
        $this->emit('removeWriteStream', [$stream]);
        $this->loop->removeWriteStream($stream);
    }

    /**
     * {@inheritDoc}
     */
    // phpcs:disable
    public function addTimer($interval, $callback)
    {
        $loopTimer = null;
        $wrapper   = function () use (&$loopTimer, $callback, $interval): void {
            $this->emit('timerTick', [$interval, $callback, $loopTimer]);
            $callback($loopTimer);
        };
        $loopTimer = $this->loop->addTimer(
            $interval,
            $wrapper
        );
        $this->emit('addTimer', [$interval, $callback, $loopTimer]);

        return $loopTimer;
    }

    /**
     * {@inheritDoc}
     */
    // phpcs:disable
    public function addPeriodicTimer($interval, $callback)
    {
        $loopTimer = $this->loop->addPeriodicTimer(
            $interval,
            function () use (&$loopTimer, $callback, $interval): void {
                $this->emit('periodicTimerTick', [$interval, $callback, $loopTimer]);
                $callback($loopTimer);
            }
        );
        $this->emit('addPeriodicTimer', [$interval, $callback, $loopTimer]);

        return $loopTimer;
    }

    public function cancelTimer(TimerInterface $timer): void
    {
        $this->emit('cancelTimer', [$timer]);

        $this->loop->cancelTimer($timer);
    }

    /**
     * {@inheritDoc}
     */
    public function futureTick($listener): void
    {
        $this->emit('futureTick', [$listener]);

        $this->loop->futureTick(function () use ($listener): void {
            $this->emit('futureTickTick', [$listener]);
            $listener($this);
        });
    }

    public function run(): void
    {
        $this->emit('runStart');
        $this->loop->run();
        $this->emit('runDone');
    }

    public function stop(): void
    {
        $this->emit('stopStart');
        $this->loop->stop();
        $this->emit('stopDone');
    }

    public function addSignal($signal, $listener): void
    {
        /**
         * @psalm-suppress InvalidArgument
         * @phpstan-ignore-next-line
         */
        $listenerId                                  = spl_object_hash($listener);
        /** @psalm-suppress MissingClosureParamType */
        $wrapper                                     = function ($signal) use ($listener): void {
            $this->emit('signalTick', [$signal, $listener]);
            $listener($signal);
        };
        $this->signalListeners[$signal][$listenerId] = $wrapper;
        $this->emit('addSignal', [$signal, $listener]);
        $this->loop->addSignal($signal, $wrapper);
    }

    public function removeSignal($signal, $listener): void
    {
        /**
         * @psalm-suppress InvalidArgument
         * @phpstan-ignore-next-line
         */
        $listenerId = spl_object_hash($listener);
        $wrapper    = $this->signalListeners[$signal][$listenerId];
        unset($this->signalListeners[$signal][$listenerId]);
        $this->emit('removeSignal', [$signal, $listener]);
        $this->loop->removeSignal($signal, $wrapper);
    }
}
