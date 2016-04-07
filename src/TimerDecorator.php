<?php

namespace WyriHaximus\React\Inspector;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class TimerDecorator implements TimerInterface
{
    /**
     * @var LoopDecorator
     */
    protected $loop;

    /**
     * @var TimerInterface
     */
    protected $timer;

    /**
     * TimerDecorator constructor.
     * @param LoopInterface $loop
     * @param TimerInterface $timer
     */
    public function __construct(LoopInterface $loop, TimerInterface $timer)
    {
        $this->loop  = $loop;
        $this->timer = $timer;
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @return float
     */
    public function getInterval()
    {
        return $this->timer->getInterval();
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->timer->getCallback();
    }

    /**
     * @param $data mixed
     */
    public function setData($data)
    {
        $this->timer->setData($data);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->timer->getData();
    }

    /**
     * @return bool
     */
    public function isPeriodic()
    {
        return $this->timer->isPeriodic();
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->timer->isActive();
    }

    public function cancel()
    {
        $this->loop->cancelTimer($this->timer);
    }
}
