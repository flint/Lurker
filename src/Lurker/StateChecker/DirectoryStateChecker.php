<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
