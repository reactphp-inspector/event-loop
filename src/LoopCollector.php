<?php

declare(strict_types=1);

namespace ReactInspector\EventLoop;

use React\EventLoop\TimerInterface;
use ReactInspector\CollectorInterface;
use ReactInspector\Config;
use ReactInspector\Measurement;
use ReactInspector\Measurements;
use ReactInspector\Metric;
use ReactInspector\Tag;
use ReactInspector\Tags;
use Rx\Observable;

use function ApiClients\Tools\Rx\observableFromArray;
use function array_key_exists;
use function count;
use function spl_object_hash;

use const WyriHaximus\Constants\Boolean\TRUE_;

final class LoopCollector implements CollectorInterface
{
    /** @var array<int, resource> */
    private array $streamsRead = [];

    /** @var array<int, resource> */
    private array $streamsWrite = [];

    /** @var array<int, resource> */
    private array $streamsDuplex = [];

    /** @var array<string, bool> */
    private array $timers = [];

    /** @var array<string, int> */
    private array $metrics = [
        'signals.ticks' => 0,
        'streams.read.ticks' => 0,
        'streams.duplex.ticks' => 0,
        'streams.write.ticks' => 0,
        'timers.periodic.ticks' => 0,
        'timers.once.ticks' => 0,
        'ticks.future.ticks' => 0,
        'ticks.future.total' => 0,
        'ticks.future.current' => 0,
        'signals.total' => 0,
        'signals.current' => 0,
        'streams.duplex.total' => 0,
        'streams.duplex.current' => 0,
        'streams.read.total' => 0,
        'streams.read.current' => 0,
        'streams.write.total' => 0,
        'streams.write.current' => 0,
        'timers.periodic.total' => 0,
        'timers.periodic.current' => 0,
        'timers.once.total' => 0,
        'timers.once.current' => 0,
    ];

    public function __construct(LoopDecorator $loop)
    {
        $this->setupTicks($loop);
        $this->setupTimers($loop);
        $this->setupStreams($loop);
        $this->setupSignals($loop);
    }

    private function setupTicks(LoopDecorator $loop): void
    {
        $loop->on('futureTick', function (): void {
            $this->metrics['ticks.future.current']++;
            $this->metrics['ticks.future.total']++;
        });
        $loop->on('futureTickTick', function (): void {
            $this->metrics['ticks.future.current']--;
            $this->metrics['ticks.future.ticks']++;
        });
    }

    private function setupTimers(LoopDecorator $loop): void
    {
        /** @psalm-suppress MissingClosureParamType */
        $loop->on('addTimer', function ($void, $null, $timer): void {
            $this->timers[spl_object_hash($timer)] = TRUE_;
            $this->metrics['timers.once.current']++;
            $this->metrics['timers.once.total']++;
        });
        /** @psalm-suppress MissingClosureParamType */
        $loop->on('timerTick', function ($void, $null, $timer): void {
            $this->metrics['timers.once.current']--;
            $this->metrics['timers.once.ticks']++;

            $hash = spl_object_hash($timer);
            if (! array_key_exists($hash, $this->timers)) {
                return;
            }

            unset($this->timers[$hash]);
        });
        /** @psalm-suppress MissingClosureParamType */
        $loop->on('addPeriodicTimer', function ($void, $null, $timer): void {
            $this->timers[spl_object_hash($timer)] = TRUE_;
            $this->metrics['timers.periodic.current']++;
            $this->metrics['timers.periodic.total']++;
        });
        $loop->on('periodicTimerTick', function (): void {
            $this->metrics['timers.periodic.ticks']++;
        });
        $loop->on('cancelTimer', function (TimerInterface $timer): void {
            $hash = spl_object_hash($timer);
            if (! array_key_exists($hash, $this->timers)) {
                return;
            }

            unset($this->timers[$hash]);

            if ($timer->isPeriodic()) {
                $this->metrics['timers.periodic.current']--;

                return;
            }

            $this->metrics['timers.once.current']--;
        });
    }

    private function setupStreams(LoopDecorator $loop): void
    {
        /** @psalm-suppress MissingClosureParamType */
        $loop->on('addReadStream', function ($stream): void {
            $key = (int) $stream;

            $this->streamsRead[$key]   = $stream;
            $this->streamsDuplex[$key] = $stream;

            $this->metrics['streams.read.current']   = count($this->streamsRead);
            $this->metrics['streams.duplex.current'] = count($this->streamsDuplex);
            $this->metrics['streams.read.total']++;
            if (array_key_exists($key, $this->streamsWrite)) {
                return;
            }

            $this->metrics['streams.duplex.total']++;
        });
        $loop->on('readStreamTick', function (): void {
            $this->metrics['streams.read.ticks']++;
            $this->metrics['streams.duplex.ticks']++;
        });
        /** @psalm-suppress MissingClosureParamType */
        $loop->on('removeReadStream', function ($stream): void {
            $key = (int) $stream;

            if (array_key_exists($key, $this->streamsRead)) {
                unset($this->streamsRead[$key]);
            }

            if (array_key_exists($key, $this->streamsRead) && ! array_key_exists($key, $this->streamsWrite)) {
                unset($this->streamsDuplex[$key]);
            }

            $this->metrics['streams.read.current']   = count($this->streamsRead);
            $this->metrics['streams.duplex.current'] = count($this->streamsDuplex);
        });

        /** @psalm-suppress MissingClosureParamType */
        $loop->on('addWriteStream', function ($stream): void {
            $key = (int) $stream;

            $this->streamsWrite[$key]  = $stream;
            $this->streamsDuplex[$key] = $stream;

            $this->metrics['streams.write.current']  = count($this->streamsWrite);
            $this->metrics['streams.duplex.current'] = count($this->streamsDuplex);
            $this->metrics['streams.write.total']++;

            if (array_key_exists($key, $this->streamsRead)) {
                return;
            }

            $this->metrics['streams.duplex.total']++;
        });
        $loop->on('writeStreamTick', function (): void {
            $this->metrics['streams.write.ticks']++;
            $this->metrics['streams.duplex.ticks']++;
        });
        /** @psalm-suppress MissingClosureParamType */
        $loop->on('removeWriteStream', function ($stream): void {
            $key = (int) $stream;

            if (array_key_exists($key, $this->streamsWrite)) {
                unset($this->streamsWrite[$key]);
            }

            if (array_key_exists($key, $this->streamsRead) && ! array_key_exists($key, $this->streamsRead)) {
                unset($this->streamsDuplex[$key]);
            }

            $this->metrics['streams.write.current']  = count($this->streamsWrite);
            $this->metrics['streams.duplex.current'] = count($this->streamsDuplex);
        });
    }

    private function setupSignals(LoopDecorator $loop): void
    {
        $loop->on('addSignal', function (): void {
            $this->metrics['signals.current']++;
            $this->metrics['signals.total']++;
        });
        $loop->on('signalTick', function (): void {
            $this->metrics['signals.ticks']++;
        });
        $loop->on('removeSignal', function (): void {
            $this->metrics['signals.current']--;
        });
    }

    public function collect(): Observable
    {
        return observableFromArray([
            Metric::create(
                new Config(
                    'reactphp_ticks',
                    'gauge',
                    ''
                ),
                new Tags(
                    new Tag('reactphp_component', 'event-loop'),
                ),
                new Measurements(
                    new Measurement($this->metrics['signals.ticks'], new Tags(new Tag('event_loop_component', 'signals'))),
                    new Measurement(
                        $this->metrics['streams.read.ticks'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'read'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['streams.duplex.ticks'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'duplex'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['streams.write.ticks'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'write'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['timers.periodic.ticks'],
                        new Tags(
                            new Tag('event_loop_component', 'timers'),
                            new Tag('timer_kind', 'periodic'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['timers.once.ticks'],
                        new Tags(
                            new Tag('event_loop_component', 'timers'),
                            new Tag('timer_kind', 'once'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['ticks.future.ticks'],
                        new Tags(
                            new Tag('event_loop_component', 'ticks'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['ticks.future.current'],
                        new Tags(
                            new Tag('event_loop_component', 'ticks'),
                            new Tag('future_tick_state', 'active'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['ticks.future.total'] - $this->metrics['ticks.future.current'],
                        new Tags(
                            new Tag('event_loop_component', 'ticks'),
                            new Tag('future_tick_state', 'done'),
                        )
                    ),
                )
            ),
            Metric::create(
                new Config(
                    'reactphp_signals',
                    'gauge',
                    ''
                ),
                new Tags(
                    new Tag('reactphp_component', 'event-loop'),
                ),
                new Measurements(
                    new Measurement(
                        $this->metrics['signals.current'],
                        new Tags(
                            new Tag('event_loop_component', 'signals'),
                            new Tag('signal_state', 'active'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['signals.total'] - $this->metrics['signals.current'],
                        new Tags(
                            new Tag('event_loop_component', 'signals'),
                            new Tag('signal_state', 'done'),
                        )
                    ),
                )
            ),
            Metric::create(
                new Config(
                    'reactphp_streams',
                    'gauge',
                    ''
                ),
                new Tags(
                    new Tag('reactphp_component', 'event-loop'),
                ),
                new Measurements(
                    new Measurement(
                        $this->metrics['streams.duplex.current'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'duplex'),
                            new Tag('stream_state', 'active'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['streams.duplex.total'] - $this->metrics['streams.duplex.current'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'duplex'),
                            new Tag('stream_state', 'done'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['streams.read.current'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'read'),
                            new Tag('stream_state', 'active'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['streams.read.total'] - $this->metrics['streams.read.current'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'read'),
                            new Tag('stream_state', 'done'),
                        ),
                    ),
                    new Measurement(
                        $this->metrics['streams.write.current'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'write'),
                            new Tag('stream_state', 'active'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['streams.write.total'] - $this->metrics['streams.write.current'],
                        new Tags(
                            new Tag('event_loop_component', 'streams'),
                            new Tag('stream_kind', 'write'),
                            new Tag('stream_state', 'done'),
                        )
                    ),
                )
            ),
            Metric::create(
                new Config(
                    'reactphp_timers',
                    'gauge',
                    ''
                ),
                new Tags(
                    new Tag('reactphp_component', 'event-loop'),
                ),
                new Measurements(
                    new Measurement(
                        $this->metrics['timers.periodic.current'],
                        new Tags(
                            new Tag('event_loop_component', 'timers'),
                            new Tag('timer_kind', 'periodic'),
                            new Tag('timer_state', 'active'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['timers.periodic.total'] - $this->metrics['timers.periodic.current'],
                        new Tags(
                            new Tag('event_loop_component', 'timers'),
                            new Tag('timer_kind', 'periodic'),
                            new Tag('timer_state', 'done'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['timers.once.current'],
                        new Tags(
                            new Tag('event_loop_component', 'timers'),
                            new Tag('timer_kind', 'once'),
                            new Tag('timer_state', 'active'),
                        )
                    ),
                    new Measurement(
                        $this->metrics['timers.once.total'] - $this->metrics['timers.once.current'],
                        new Tags(
                            new Tag('event_loop_component', 'timers'),
                            new Tag('timer_kind', 'once'),
                            new Tag('timer_state', 'done'),
                        )
                    ),
                )
            ),
        ]);
    }

    public function cancel(): void
    {
        // Do nothing
    }
}
