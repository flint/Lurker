<?php

namespace Lurker\Resource;

use Lurker\Exception\InvalidArgumentException;

/**
 * @package Lurker
 */
class FileResource implements ResourceInterface
{
    /**
     * @var string
     */
    private $resource;

    /**
     * @param string $resource
     */
    public function __construct($resource)
    {
        $this->resource = realpath($resource);

        if (false === $this->resource && file_exists($resource)) {
            $this->resource = $resource;
        }

        if (false === $this->resource) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $resource));
        }
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->resource;
    }

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
