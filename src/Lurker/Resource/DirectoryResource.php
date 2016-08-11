<?php

namespace Lurker\Resource;

use Lurker\Exception\InvalidArgumentException;

/**
 * @package Lurker
 */
class DirectoryResource implements ResourceInterface
{
    /**
     * @var string
     */
    private $resource;

    /**
     * @var string|null
     */
    private $pattern;

    /**
     * @param string $resource
     * @param string|null $pattern
     */
    public function __construct($resource, $pattern = null)
    {
        $this->resource = realpath($resource);
        $this->pattern = $pattern;

        if (false === $this->resource || !is_dir($this->resource)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" does not exist.', $resource));
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
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return md5(serialize(array($this->resource, $this->pattern)));
    }

    public function exists()
    {
        clearstatcache(true, $resource = $this->getResource());

        return is_dir($resource);
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

    public function isFresh($timestamp)
    {
        if (!$this->exists()) {
            return false;
        }

        return $this->getModificationTime() < $timestamp;
    }

    public function getId()
    {
        return md5('d' . $this . $this->getPattern());
    }

    public function hasFile($file)
    {
        if (!$file instanceof \SplFileInfo) {
            $file = new \SplFileInfo($file);
        }

        if (0 !== strpos($file->getRealPath(), realpath($this->getResource()))) {
            return false;
        }

        if ($this->getPattern()) {
            return (bool) preg_match($this->getPattern(), $file->getBasename());
        }

        return true;
    }

    public function getFilteredResources()
    {
        if (!$this->exists()) {
            return array();
        }

        // race conditions
        try {
            $iterator = new \DirectoryIterator($this->getResource());
        } catch (\UnexpectedValueException $e) {
            return array();
        }

        $resources = array();
        foreach ($iterator as $file) {
            // if regex filtering is enabled only return matching files
            if ($file->isFile() && !$this->hasFile($file)) {
                continue;
            }

            // always monitor directories for changes, except the .. entries
            // (otherwise deleted files wouldn't get detected)
            if ($file->isDir() && '/..' === substr($file, -3)) {
                continue;
            }

            // if file is dot - continue
            if ($file->isDot()) {
                continue;
            }

            if ($file->isFile()) {
                $resources[] = new FileResource($file->getRealPath());
            } elseif ($file->isDir()) {
                $resources[] = new DirectoryResource($file->getRealPath());
            }
        }

        return $resources;
    }
}
