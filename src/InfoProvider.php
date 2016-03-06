<?php

namespace WyriHaximus\React\Inspector;

use React\EventLoop\Timer\TimerInterface;

class InfoProvider
{
    /**
     * @var LoopDecorator
     */
    protected $loop;

    /**
     * @var array
     */
    protected $counters = [];

    /**
     * @var array
     */
    protected $streamsRead = [];

    /**
     * @var array
     */
    protected $streamsWrite = [];

    /**
     * @var array
     */
    protected $streamsDuplex = [];

    /**
     * @param LoopDecorator $loop
     */
    public function __construct(LoopDecorator $loop)
    {
        $this->loop = $loop;
        $this->reset();

        $loop->on('futureTick', function () {
            $this->counters['ticks']['future']['current']++;
            $this->counters['ticks']['future']['total']++;
        });
        $loop->on('futureTickTick', function () {
            $this->counters['ticks']['future']['current']--;
            $this->counters['ticks']['future']['ticks']++;
        });
        $loop->on('nextTick', function () {
            $this->counters['ticks']['next']['current']++;
            $this->counters['ticks']['next']['total']++;
        });
        $loop->on('nextTickTick', function () {
            $this->counters['ticks']['next']['current']--;
            $this->counters['ticks']['next']['ticks']++;
        });

        $loop->on('addTimer', function () {
            $this->counters['timers']['once']['current']++;
            $this->counters['timers']['once']['total']++;
        });
        $loop->on('timerTick', function () {
            $this->counters['timers']['once']['current']--;
            $this->counters['timers']['once']['ticks']++;
        });
        $loop->on('addPeriodicTimer', function () {
            $this->counters['timers']['periodic']['current']++;
            $this->counters['timers']['periodic']['total']++;
        });
        $loop->on('periodicTimerTick', function () {
            $this->counters['timers']['periodic']['ticks']++;
        });
        $loop->on('cancelTimer', function (TimerInterface $timer) {
            if ($timer->isPeriodic()) {
                $this->counters['timers']['periodic']['current']--;
            }
        });

        $loop->on('addReadStream', function ($stream) {
            $key = (int) $stream;

            $this->streamsRead[$key] = $stream;
            $this->streamsDuplex[$key] = $stream;

            $this->counters['streams']['read']['current'] = count($this->streamsRead);
            $this->counters['streams']['total']['current'] = count($this->streamsDuplex);
            $this->counters['streams']['read']['total']++;
            if (!isset($this->streamsWrite[$key])) {
                $this->counters['streams']['total']['total']++;
            }
        });
        $loop->on('readStreamTick', function () {
            $this->counters['streams']['read']['ticks']++;
            $this->counters['streams']['total']['ticks']++;
        });
        $loop->on('removeReadStream', function ($stream) {
            $key = (int) $stream;

            if (isset($this->streamsRead[$key])) {
                unset($this->streamsRead[$key]);
            }
            if (isset($this->streamsDuplex[$key]) && !isset($this->streamsWrite[$key])) {
                unset($this->streamsDuplex[$key]);
            }
            $this->counters['streams']['read']['current'] = count($this->streamsRead);
            $this->counters['streams']['total']['current'] = count($this->streamsDuplex);
        });

        $loop->on('addWriteStream', function ($stream) {
            $key = (int) $stream;

            $this->streamsWrite[$key] = $stream;
            $this->streamsDuplex[$key] = $stream;

            $this->counters['streams']['write']['current'] = count($this->streamsWrite);
            $this->counters['streams']['total']['current'] = count($this->streamsDuplex);
            $this->counters['streams']['write']['total']++;
            if (!isset($this->streamsRead[$key])) {
                $this->counters['streams']['total']['total']++;
            }
        });
        $loop->on('writeStreamTick', function () {
            $this->counters['streams']['write']['ticks']++;
            $this->counters['streams']['total']['ticks']++;
        });
        $loop->on('removeWriteStream', function ($stream) {
            $key = (int) $stream;

            if (isset($this->streamsWrite[$key])) {
                unset($this->streamsWrite[$key]);
            }
            if (isset($this->streamsDuplex[$key]) && !isset($this->streamsRead[$key])) {
                unset($this->streamsDuplex[$key]);
            }
            $this->counters['streams']['write']['current'] = count($this->streamsWrite);
            $this->counters['streams']['total']['current'] = count($this->streamsDuplex);
        });

        $loop->on('removeStream', function ($stream) {
            $key = (int) $stream;

            if (isset($this->streamsWrite[$key])) {
                unset($this->streamsWrite[$key]);
            }
            if (isset($this->streamsDuplex[$key])) {
                unset($this->streamsDuplex[$key]);
            }
            $this->counters['streams']['write']['current'] = count($this->streamsWrite);
            $this->counters['streams']['total']['current'] = count($this->streamsDuplex);
        });
    }

    public function reset()
    {
        $this->counters = [
            'streams' => [
                'read' => [
                    'min'       => 0,
                    'current'   => 0,
                    'max'       => 0,
                    'total'     => 0,
                    'ticks'     => 0,
                ],
                'total' => [
                    'min'       => 0,
                    'current'   => 0,
                    'max'       => 0,
                    'total'     => 0,
                    'ticks'     => 0,
                ],
                'write' => [
                    'min'       => 0,
                    'current'   => 0,
                    'max'       => 0,
                    'total'     => 0,
                    'ticks'     => 0,
                ],
            ],
            'timers' => [
                'once' => [
                    'current'   => 0,
                    'total'     => 0,
                    'ticks'     => 0,
                ],
                'periodic' => [
                    'current'   => 0,
                    'total'     => 0,
                    'ticks'     => 0,
                ],
            ],
            'ticks' => [
                'future' => [
                    'current'   => 0,
                    'total'     => 0,
                    'ticks'     => 0,
                ],
                'next' => [
                    'current'   => 0,
                    'total'     => 0,
                    'ticks'     => 0,
                ],
            ],
        ];
    }

    public function resetTotals()
    {
        $this->counters['streams']['read']['total'] = 0;
        $this->counters['streams']['total']['total'] = 0;
        $this->counters['streams']['write']['total'] = 0;
        $this->counters['timers']['once']['total'] = 0;
        $this->counters['timers']['periodic']['total'] = 0;
        $this->counters['ticks']['future']['total'] = 0;
        $this->counters['ticks']['next']['total'] = 0;
    }

    public function resetTicks()
    {
        $this->counters['streams']['read']['ticks'] = 0;
        $this->counters['streams']['total']['ticks'] = 0;
        $this->counters['streams']['write']['ticks'] = 0;
        $this->counters['timers']['once']['ticks'] = 0;
        $this->counters['timers']['periodic']['ticks'] = 0;
        $this->counters['ticks']['future']['ticks'] = 0;
        $this->counters['ticks']['next']['ticks'] = 0;
    }

    /**
     * @return array
     */
    public function getCounters()
    {
        return $this->counters;
    }
}
