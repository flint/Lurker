<?php

namespace Lurker\Event;

use Lurker\ResourceWatcher;
use Symfony\Component\EventDispatcher\Event;

/**
 * ResourceWatcher event.
 */
class ResourceWatcherEvent extends Event
{
    const START = 'resource_watcher.start';
    const STOP = 'resource_watcher.stop';
    const PERIOD = 'resource_watcher.period';

    private $watcher;

    /**
     * Initializes the ResourceWatcher event
     *
     * @param ResourceWatcher $watcher
     * @param string          $type
     */
    public function __construct(ResourceWatcher $watcher)
    {
        $this->watcher = $watcher;
    }

    /**
     * Returns ResourceWatcher.
     *
     * @return ResourceWatcher
     */
    public function getResourceWatcher()
    {
        return $this->watcher;
    }
}
