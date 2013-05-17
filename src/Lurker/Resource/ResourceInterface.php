<?php

namespace Lurker\Resource;

use Symfony\Component\Config\Resource\ResourceInterface as BaseResourceInterface;

/**
 * @package Lurker
 */
interface ResourceInterface extends BaseResourceInterface
{
    /**
     * @return boolean
     */
    public function exists();

    /**
     * @return integer
     */
    public function getModificationTime();

    /**
     * @return string
     */
    public function getId();
}
