<?php

namespace Lurker;

use Lurker\Event\FilesystemEvent;
use Lurker\Exception\InvalidArgumentException;
use Lurker\Resource\DirectoryResource;
use Lurker\Resource\FileResource;
use Lurker\Resource\ResourceInterface;
use Lurker\Resource\TrackedResource;
use Lurker\Tracker\InotifyTracker;
use Lurker\Tracker\RecursiveIteratorTracker;
use Lurker\Tracker\TrackerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Resource changes watcher.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ResourceWatcher
{
    private $tracker;
    private $eventDispatcher;
    private $watching = false;

    /**
     * Initializes path watcher.
     *
     * @param TrackerInterface         $tracker
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(TrackerInterface $tracker = null, EventDispatcherInterface $eventDispatcher = null)
    {
        if (null === $tracker) {
            if (function_exists('inotify_init')) {
                $tracker = new InotifyTracker();
            } else {
                $tracker = new RecursiveIteratorTracker();
            }
        }

        if (null === $eventDispatcher) {
            $eventDispatcher = new EventDispatcher();
        }

        $this->tracker         = $tracker;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns current tracker instance.
     *
     * @return TrackerInterface
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * Returns event dispatcher mapped to this tracker.
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Track resource with watcher.
     *
     * @param string                   $trackingId id to this track (used for events naming)
     * @param ResourceInterface|string $resource   resource to track
     * @param integer                  $eventsMask event types bitmask
     *
     * @throws InvalidArgumentException If 'all' is used as a tracking id
     */
    public function track($trackingId, $resource, $eventsMask = FilesystemEvent::ALL)
    {
        if ('all' === $trackingId) {
            throw new InvalidArgumentException(
                '"all" is a reserved keyword and can not be used as tracking id'
            );
        }

        if (!$resource instanceof ResourceInterface) {
            if (is_file($resource)) {
                $resource = new FileResource($resource);
            } elseif (is_dir($resource)) {
                $resource = new DirectoryResource($resource);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Second argument to track() should be either file or directory resource, '.
                    'but got "%s"',
                    $resource
                ));
            }
        }

        $trackedResource = new TrackedResource($trackingId, $resource);
        $this->getTracker()->track($trackedResource, $eventsMask);
    }

    /**
     * Adds callback as specific tracking listener.
     *
     * @param string   $trackingId id to this track (used for events naming)
     * @param callable $callback   callback to call on change
     *
     * @throws InvalidArgumentException If $callback argument isn't callable
     */
    public function addListener($trackingId, $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException(sprintf(
                'Second argument to listen() should be callable, but got %s', gettype($callback)
            ));
        }

        $this->getEventDispatcher()->addListener('resource_watcher.'.$trackingId, $callback);
    }

    /**
     * Tracks specific resource change by provided callback.
     *
     * @param ResourceInterface|string $resource   resource to track
     * @param callable                 $callback   callback to call on change
     * @param integer                  $eventsMask event types bitmask
     */
    public function trackByListener($resource, $callback, $eventsMask = FilesystemEvent::ALL)
    {
        $this->track($trackingId = md5((string) $resource.$eventsMask), $resource, $eventsMask);
        $this->addListener($trackingId, $callback);
    }

    /**
     * Returns true if watcher is currently watching on tracked resources (started).
     *
     * @return Boolean
     */
    public function isWatching()
    {
        return $this->watching;
    }

    /**
     * Starts watching on tracked resources.
     *
     * @param integer $checkInterval check interval in microseconds
     * @param integer $timeLimit     maximum watching time limit in microseconds
     */
    public function start($checkInterval = 1000000, $timeLimit = null)
    {
        $totalTime = 0;
        $this->watching = true;

        while ($this->watching) {
            usleep($checkInterval);
            $totalTime += $checkInterval;

            if (null !== $timeLimit && $totalTime > $timeLimit) {
                break;
            }

            foreach ($this->getTracker()->getEvents() as $event) {
                $trackedResource = $event->getTrackedResource();

                // fire global event
                $this->getEventDispatcher()->dispatch(
                    'resource_watcher.all',
                    $event
                );

                // fire specific trackingId event
                $this->getEventDispatcher()->dispatch(
                    sprintf('resource_watcher.%s', $trackedResource->getTrackingId()),
                    $event
                );
            }
        }

        $this->watching = false;
    }

    /**
     * Stop watching on tracked resources.
     */
    public function stop()
    {
        $this->watching = false;
    }
}
