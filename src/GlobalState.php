<?php declare(strict_types=1);

namespace WyriHaximus\React\Inspector;

final class GlobalState
{
    protected static $state = [];

    public static function get(): array
    {
        return self::$state;
    }

    public static function reset()
    {
        self::$state = [];
    }

    public static function set(string $key, int $value)
    {
        self::$state[$key] = $value;
    }

    public static function incr(string $key, int $value = 1)
    {
        if (!isset(self::$state[$key])) {
            self::$state[$key] = 0;
        }

        self::$state[$key] += $value;
    }

    public static function decr(string $key, int $value = 1)
    {
        if (!isset(self::$state[$key])) {
            self::$state[$key] = 0;
        }

        self::$state[$key] -= $value;
    }
}
