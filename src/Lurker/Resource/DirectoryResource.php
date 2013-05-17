<?php

namespace Lurker\Resource;

use Symfony\Component\Config\Resource\DirectoryResource as BaseDirectoryResource;

/**
 * @package Lurker
 */
class DirectoryResource extends BaseDirectoryResource implements ResourceInterface
{

    public function exists()
    {
        return is_dir($this);
    }

    public function getModificationTime()
    {
        if (!$this->exists()) {
            return -1;
        }

        $resource = $this->getResource();
        clearstatcache(true, $resource);
        $newestMTime = filemtime($resource);

        foreach ($this->getFilteredChilds() as $file) {
            clearstatcache(true, (string) $file);
            $newestMTime = max($file->getMTime(), $newestMTime);
        }

        return $newestMTime;
    }

    /**
     * Returns the list of filtered file and directory childs of directory resource.
     *
     * @return array An array of files
     */
    public function getFilteredChilds()
    {
        if (!$this->exists()) {
            return array();
        }

        $resource = $this->getResource();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($resource, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $childs = array();
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

            $childs[] = $file;
        }

        return $childs;
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

        $iterator = new \DirectoryIterator($this->getResource());

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
