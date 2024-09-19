<?php

declare(strict_types=1);

namespace ReactInspector\EventLoop;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use ReactInspector\GlobalState;
use WyriHaximus\Metrics\LazyRegistry\Registry as LazyRegistry;
use WyriHaximus\Metrics\Registry;

const BOOTSTRAPPED = true;

/** @psalm-suppress InternalMethod */
Loop::set((static function (LoopInterface $loop): LoopInterface {
    $lazyRegistry = new LazyRegistry();
    GlobalState::subscribe(static fn (Registry $registry) => $lazyRegistry->register($registry));

    return new LoopDecorator($loop, $lazyRegistry);
})(Loop::get()));
