<?php

use React\EventLoop\Factory;
use React\EventLoop\Timer\TimerInterface;
use WyriHaximus\React\Inspector\LoopDecorator;

require 'vendor/autoload.php';

$loop = new LoopDecorator(Factory::create());

for ($i = 1; $i <= 3; $i++) {
    $loop->addTimer($i, function () {});
    $loop->addPeriodicTimer(1, function (TimerInterface $timer) {
        if (mt_rand(0, 10) == mt_rand(0, 10)) {
            $timer->cancel();
        }
    });
    $loop->nextTick(function () use ($loop) {
        $loop->nextTick(function () {});
    });
    $loop->futureTick(function () use ($loop) {
        $loop->futureTick(function () {});
    });
}

$loop->run();

var_export((array) $loop->getReccordings());
