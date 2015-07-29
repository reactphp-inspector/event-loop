<?php

namespace WyriHaximus\React\Inspector;

class Timer implements \JsonSerializable
{
    /**
     * @var float
     */
    protected $start;

    /**
     * @var float
     */
    protected $stop;

    /**
     * @var float
     */
    protected $total;

    /**
     * @return Timer
     */
    public static function start()
    {
        return new static();
    }

    protected function __construct()
    {
        $this->stop = 0.0;
        $this->start = microtime(true);
    }

    /**
     * @throws \Exception
     */
    public function stop()
    {
        $this->stop = microtime(true);
        if ($this->start === 0) {
            throw new \Exception('Timer not started');
        }
        $this->total = $this->stop - $this->start;
    }

    /**
     * @return float
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return float
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize()
    {
        return [
            'start' => $this->start,
            'stop'  => $this->stop,
            'total' => $this->total,
        ];
    }
}
