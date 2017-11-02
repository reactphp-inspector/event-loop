<?php declare(strict_types=1);

namespace WyriHaximus\React\Tests\Inspector;

use PHPUnit\Framework\TestCase;
use WyriHaximus\React\Inspector\GlobalState;

final class GlobalStateTest extends TestCase
{
    public function testGlobalState()
    {
        self::assertSame([], GlobalState::get());
        GlobalState::set('key', 1);
        self::assertSame(['key' => 1], GlobalState::get());
        GlobalState::incr('key');
        self::assertSame(['key' => 2], GlobalState::get());
        GlobalState::incr('key', 3);
        self::assertSame(['key' => 5], GlobalState::get());
        GlobalState::reset();
        self::assertSame([], GlobalState::get());
        GlobalState::incr('key', 3);
        self::assertSame(['key' => 3], GlobalState::get());
        GlobalState::decr('key');
        self::assertSame(['key' => 2], GlobalState::get());
    }
}
