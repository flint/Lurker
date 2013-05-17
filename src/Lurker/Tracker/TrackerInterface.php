<?php

namespace Lurker\Tracker;

use Lurker\Resource\TrackedResource;
use Lurker\Event\FilesystemEvent;

/**
 * Resources tracker interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface TrackerInterface
{
    /**
     * Starts to track provided resource for changes.
     *
     * @param TrackedResource $resource
     * @param integer         $eventsMask event types bitmask
     */
    public function track(TrackedResource $resource, $eventsMask = FilesystemEvent::ALL);

    /**
     * Checks tracked resources for change events.
     *
     * @return array change events array
     */
    public function getEvents();
}
