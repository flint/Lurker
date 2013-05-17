<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lurker\Tests\Tracker;

use Lurker\Tracker\RecursiveIteratorTracker;

class RecursiveIteratorTrackerTest extends TrackerTest
{
    /**
     * @return TrackerInterface
     */
    protected function getTracker()
    {
        return new RecursiveIteratorTracker();
    }

    protected function getMinimumInterval()
    {
        return 2000000;
    }
}
