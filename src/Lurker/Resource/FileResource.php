<?php

namespace Lurker\Resource;

use Symfony\Component\Config\Resource\FileResource as BaseFileResource;

/**
 * @package Lurker
 */
class FileResource extends BaseFileResource implements ResourceInterface
{
    public function getModificationTime()
    {
        if (!$this->exists()) {
            return -1;
        }

        $resource = $this->getResource();

        clearstatcache(true, $resource);

        return filemtime($resource);
    }

    public function getId()
    {
        return md5('f' . $this);
    }

    public function isFresh($timestamp)
    {
        if (!$this->exists()) {
            return false;
        }

        return $this->getModificationTime() < $timestamp;
    }

    public function exists()
    {
        return is_file($this);
    }

}
