<?php

namespace WyriHaximus\React\Inspector;

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
     */
    public function __construct(LoopDecorator $loop, TimerInterface $timer)
    {
        $this->loop  = $loop;
        $this->timer = $timer;
    }

    public function getLoop()
    {
        return $this->loop;
    }

    public function getInterval()
    {
        return $this->timer->getInterval();
    }

    public function getCallback()
    {
        return $this->timer->getCallback();
    }

    public function setData($data)
    {
        return $this->timer->setData($data);
    }

    public function getData()
    {
        return $this->timer->getData();
    }

    public function isPeriodic()
    {
        return $this->timer->isPeriodic();
    }

    public function isActive()
    {
        return $this->timer->isActive();
    }

    public function cancel()
    {
        $this->loop->cancelTimer($this->timer);
    }
}
