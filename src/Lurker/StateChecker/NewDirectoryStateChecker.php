<?php

namespace Lurker\StateChecker;

use Lurker\Event\FilesystemEvent;
use Lurker\Resource\DirectoryResource;

/**
 * Recursive directory state checker.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class NewDirectoryStateChecker extends ResourceStateChecker
{
    protected $childs = array();

    /**
     * Initializes checker.
     *
     * @param DirectoryResource $resource
     * @param integer           $eventsMask event types bitmask
     */
    public function __construct(DirectoryResource $resource, $eventsMask = FilesystemEvent::ALL)
    {
        parent::__construct($resource, $eventsMask);
    }

    /**
     * {@inheritdoc}
     */
    public function getChangeset()
    {
        $changeset = parent::getChangeset();

        // remove directory modification from changeset
        if (isset($changeset[0]) && FilesystemEvent::MODIFY === $changeset[0]['event']) {
            $changeset = array();
        }

        // check for changes in already added subfolders/files
        foreach ($this->childs as $id => $checker) {
            foreach ($checker->getChangeset() as $change) {
                if ($this->supportsEvent($change['event'])) {
                    $changeset[] = $change;
                }
            }

            // remove checkers for removed resources
            if (!$checker->getResource()->exists()) {
                unset($this->childs[$id]);
            }
        }

        // check for new subfolders/files
        if ($this->getResource()->exists()) {
            foreach ($this->createNewDirectoryChildCheckers($this->getResource()) as $checker) {
                $resource   = $checker->getResource();
                $resourceId = $resource->getId();

                if (!isset($this->childs[$resourceId])) {
                    $this->childs[$resourceId] = $checker;

                    if ($this->supportsEvent($event = FilesystemEvent::CREATE)) {
                        $changeset[] = array(
                            'event'    => $event,
                            'resource' => $resource
                        );
                    }

                    // check for new directory changes
                    if ($checker instanceof NewDirectoryStateChecker) {
                        foreach ($checker->getChangeset() as $change) {
                            if ($this->supportsEvent($change['event'])) {
                                $changeset[] = $change;
                            }
                        }
                    }
                }
            }
        }

        return $changeset;
    }

    /**
     * Reads files and subdirectories on provided resource path and transform them to resources.
     *
     * @param DirectoryResource $resource
     *
     * @return array
     */
    protected function createDirectoryChildCheckers(DirectoryResource $resource)
    {
        $checkers = array();
        foreach ($resource->getFilteredResources() as $resource) {
            if ($resource instanceof DirectoryResource) {
                $checkers[] = new DirectoryStateChecker($resource, $this->getEventsMask());
            } else {
                $checkers[] = new FileStateChecker($resource, $this->getEventsMask());
            }
        }

        return $checkers;
    }

    /**
     * Reads files and subdirectories on provided resource path and transform them to resources.
     *
     * @param DirectoryResource $resource
     *
     * @return array
     */
    protected function createNewDirectoryChildCheckers(DirectoryResource $resource)
    {
        $checkers = array();
        foreach ($resource->getFilteredResources() as $resource) {
            if ($resource instanceof DirectoryResource) {
                $checkers[] = new NewDirectoryStateChecker($resource, $this->getEventsMask());
            } else {
                $checkers[] = new FileStateChecker($resource, $this->getEventsMask());
            }
        }

        return $checkers;
    }
}
