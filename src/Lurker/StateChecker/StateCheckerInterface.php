<?php

namespace Lurker\StateChecker;

use Lurker\Resource\ResourceInterface;

/**
 * Resource state checker interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface StateCheckerInterface
{
    /**
     * Returns tracked resource.
     *
     * @return ResourceInterface
     */
    public function getResource();

    /**
     * Check tracked resource for changes.
     *
     * @return array
     */
    public function getChangeset();
}
