<?php

namespace Lurker\Tests\StateChecker\Inotify\Fixtures;

use Lurker\StateChecker\Inotify\TopDirectoryStateChecker;

class TopDirectoryStateCheckerForTest extends TopDirectoryStateChecker
{
    private static $watchId;

    public static function setAddWatchReturns($value)
    {
        self::$watchId = $value;
    }

    protected function addWatch()
    {
        return self::$watchId;
    }

    protected function unwatch($id)
    {
    }
}
