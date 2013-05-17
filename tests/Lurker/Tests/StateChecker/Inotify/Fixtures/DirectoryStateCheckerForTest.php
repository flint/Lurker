<?php

namespace Lurker\Tests\StateChecker\Inotify\Fixtures;

use Lurker\StateChecker\Inotify\DirectoryStateChecker;

class DirectoryStateCheckerForTest extends DirectoryStateChecker
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
