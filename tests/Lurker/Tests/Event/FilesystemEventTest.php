<?php

namespace Lurker\Tests\Event;

use Lurker\Resource\FileResource;
use Lurker\Resource\DirectoryResource;
use Lurker\Event\FilesystemEvent;
use Lurker\Resource\TrackedResource;

class FilesystemEventTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructAndGetters()
    {
        $event = new FilesystemEvent(
            $tracked  = new TrackedResource(23, new DirectoryResource(__DIR__)),
            $resource = new FileResource(__FILE__),
            $type     = FilesystemEvent::MODIFY
        );

        $this->assertSame($tracked, $event->getTrackedResource());
        $this->assertSame($resource, $event->getResource());
        $this->assertSame($type, $event->getType());
    }

    public function testIsFileChange()
    {
        $event = new FilesystemEvent(
            $tracked  = new TrackedResource(23, new DirectoryResource(__DIR__.'/../')),
            $resource = new FileResource(__FILE__),
            $type     = FilesystemEvent::MODIFY
        );

        $this->assertTrue($event->isFileChange());
        $this->assertFalse($event->isDirectoryChange());
    }

    public function testIsDirectoryChange()
    {
        $event = new FilesystemEvent(
            $tracked  = new TrackedResource(23, new DirectoryResource(__DIR__.'/../')),
            $resource = new DirectoryResource(__DIR__),
            $type     = FilesystemEvent::MODIFY
        );

        $this->assertFalse($event->isFileChange());
        $this->assertTrue($event->isDirectoryChange());
    }

    public function testType()
    {
        $event = new FilesystemEvent(
            new TrackedResource(23, new DirectoryResource(__DIR__.'/../')),
            new DirectoryResource(__DIR__),
            FilesystemEvent::MODIFY
        );

        $this->assertSame(FilesystemEvent::MODIFY, $event->getType());
        $this->assertSame('modify', $event->getTypeString());

        $event = new FilesystemEvent(
            new TrackedResource(23, new DirectoryResource(__DIR__.'/../')),
            new DirectoryResource(__DIR__),
            FilesystemEvent::DELETE
        );

        $this->assertSame(FilesystemEvent::DELETE, $event->getType());
        $this->assertSame('delete', $event->getTypeString());

        $event = new FilesystemEvent(
            new TrackedResource(23, new DirectoryResource(__DIR__.'/../')),
            new DirectoryResource(__DIR__),
            FilesystemEvent::CREATE
        );

        $this->assertSame(FilesystemEvent::CREATE, $event->getType());
        $this->assertSame('create', $event->getTypeString());
    }
}
