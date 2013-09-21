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

        clearstatcache(true, $this->getResource());
        if (false === $mtime = @filemtime($this->getResource())) {
            return -1;
        }

        return $mtime;
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
        clearstatcache(true, $this->getResource());

        return is_file($this);
    }
}
