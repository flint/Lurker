<?php

namespace Lurker\StateChecker;

use Lurker\Event\FilesystemEvent;
use Lurker\Resource\DirectoryResource;

/**
 * Recursive directory state checker.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class DirectoryStateChecker extends NewDirectoryStateChecker
{
    /**
     * {@inheritdoc}
     */
    public function __construct(DirectoryResource $resource, $eventsMask = FilesystemEvent::ALL)
    {
        parent::__construct($resource, $eventsMask);

        foreach ($this->createDirectoryChildCheckers($resource) as $checker) {
            $this->childs[$checker->getResource()->getId()] = $checker;
        }
    }
}
