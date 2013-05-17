<?php

namespace Lurker\Tests\StateChecker\Inotify\Fixtures;

use Lurker\StateChecker\Inotify\FileStateChecker;

class FileStateCheckerForTest extends FileStateChecker
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
