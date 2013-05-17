<?php

namespace Lurker\StateChecker;

use Lurker\Resource\FileResource;
use Lurker\Event\FilesystemEvent;

/**
 * File state checker.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class FileStateChecker extends ResourceStateChecker
{
    /**
     * Initializes checker.
     *
     * @param FileResource $resource
     * @param integer      $eventsMask event types bitmask
     */
    public function __construct(FileResource $resource, $eventsMask = FilesystemEvent::ALL)
    {
        parent::__construct($resource, $eventsMask);
    }
}
