<?php

namespace Lurker\Tests\Tracker;

use Lurker\Tracker\RecursiveIteratorTracker;

/**
 * @group medium
 */
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
