<?php

namespace Lurker\StateChecker\Inotify;

use Lurker\Resource\DirectoryResource;
use Lurker\Event\FilesystemEvent;

/**
 * Directory state checker. Sets for itself and children a flag that indicates newness.
 *
 * @author Yaroslav Kiliba <om.dattaya@gmail.com>
 */
class NewDirectoryStateChecker extends DirectoryStateChecker
{
    /**
     * @var bool|null
     */
    protected $isNew = true;

    /**
     * Initializes checker.
     *
     * @param CheckerBag        $bag
     * @param DirectoryResource $resource
     * @param int               $eventsMask
     */
    public function __construct(CheckerBag $bag, DirectoryResource $resource, $eventsMask = FilesystemEvent::ALL)
    {
        $this->setEvent(IN_CREATE);
        parent::__construct($bag, $resource, $eventsMask);
    }

    /**
     * {@inheritdoc}
     */
    protected function createChildCheckers()
    {
        foreach ($this->getResource()->getFilteredResources() as $resource) {
            $basename = basename((string) $resource);
            if ($resource instanceof DirectoryResource) {
                $this->createNewDirectoryChecker($basename, $resource);
            } else {
                $this->files[$basename] = $resource;
                $this->fileEvents[$basename] = 'new';
            }
        }
    }
}
