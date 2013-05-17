<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
