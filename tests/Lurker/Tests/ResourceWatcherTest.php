<?php

namespace Lurker\Tests;

use Lurker\ResourceWatcher;
use Lurker\Resource\TrackedResource;
use Lurker\Event\FilesystemEvent;
use Lurker\Resource\FileResource;
use Lurker\Resource\DirectoryResource;

class ResourceWatcherTest extends \PHPUnit_Framework_TestCase
{
    private $tracker;
    private $dispatcher;

    protected function setUp()
    {
        $this->tracker = $this
            ->getMockBuilder('Lurker\\Tracker\\TrackerInterface')
            ->getMock();

        $this->dispatcher = $this
            ->getMockBuilder('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface')
            ->getMock();
    }

    public function testConstructor()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);

        $this->assertSame($this->tracker, $watcher->getTracker());
        $this->assertSame($this->dispatcher, $watcher->getEventDispatcher());
    }

    public function testConstructorDefaults()
    {
        $watcher = new ResourceWatcher;

        if (function_exists('inotify_init')) {
            $this->assertInstanceOf(
                'Lurker\\Tracker\\InotifyTracker',
                $watcher->getTracker()
            );
        } else {
            $this->assertInstanceOf(
                'Lurker\\Tracker\\RecursiveIteratorTracker',
                $watcher->getTracker()
            );
        }

        $this->assertInstanceOf(
            'Symfony\\Component\\EventDispatcher\\EventDispatcher',
            $watcher->getEventDispatcher()
        );
    }

    public function testTrackResource()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);

        $resource = $this->getResourceMock();
        $tracked  = new TrackedResource('twig.templates', $resource);

        $this->tracker
            ->expects($this->once())
            ->method('track')
            ->with($tracked, FilesystemEvent::ALL);

        $watcher->track('twig.templates', $resource);
    }

    public function testTrackFilepath()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);

        $resource = __FILE__;
        $tracked  = new TrackedResource('twig.templates', new FileResource($resource));

        $this->tracker
            ->expects($this->once())
            ->method('track')
            ->with($tracked);

        $watcher->track('twig.templates', $resource);
    }

    public function testTrackDirpath()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);

        $resource = __DIR__;
        $tracked  = new TrackedResource('twig.templates', new DirectoryResource($resource));

        $this->tracker
            ->expects($this->once())
            ->method('track')
            ->with($tracked);

        $watcher->track('twig.templates', $resource);
    }

    /**
     * @expectedException Lurker\Exception\InvalidArgumentException
     * @expectedExceptionMessage First argument to track() should be either file or directory
     * resource, but got "unexisting_something"
     */
    public function testTrackUnexistingResource()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);
        $watcher->track('twig.templates', 'unexisting_something');
    }

    /**
     * @expectedException Lurker\Exception\InvalidArgumentException
     * @expectedExceptionMessage "all" is a reserved keyword and can not be used as tracking id
     */
    public function testTrackReservedKeyword()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);
        $watcher->track('all', __FILE__);
    }

    public function testListenWithCallback()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);

        $callback = function() {};

        $this->dispatcher
            ->expects($this->once())
            ->method('addListener')
            ->with('resource_watcher.twig.templates', $callback);

        $watcher->addListener('twig.templates', $callback);
    }

    /**
     * @expectedException Lurker\Exception\InvalidArgumentException
     * @expectedExceptionMessage Second argument to listen() should be callable, but got string
     */
    public function testListenWithWrongCallback()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);
        $watcher->addListener('twig.templates', 'string');
    }

    public function testTrackBy()
    {
        $callback = function() {};

        $watcher = $this
            ->getMockBuilder('Lurker\\ResourceWatcher')
            ->disableOriginalConstructor()
            ->setMethods(array('track', 'addListener'))
            ->getMock();
        $watcher
            ->expects($this->once())
            ->method('track')
            ->with(md5(__FILE__.FilesystemEvent::MODIFY), __FILE__, FilesystemEvent::MODIFY);
        $watcher
            ->expects($this->once())
            ->method('addListener')
            ->with(md5(__FILE__.FilesystemEvent::MODIFY), $callback);

        $watcher->trackByListener(__FILE__, $callback, FilesystemEvent::MODIFY);
    }

    public function testTracking()
    {
        $watcher = new ResourceWatcher($this->tracker, $this->dispatcher);

        $this->tracker
            ->expects($this->once())
            ->method('getEvents')
            ->will($this->returnValue(array(
                $e1 = $this->getFSEventMock(), $e2 = $this->getFSEventMock()
            )));

        $e1
            ->expects($this->once())
            ->method('getTrackedResource')
            ->will($this->returnValue($this->getTrackedResourceMock('trackingId#1')));
        $e2
            ->expects($this->once())
            ->method('getTrackedResource')
            ->will($this->returnValue($this->getTrackedResourceMock('trackingId#2')));

        $this->dispatcher
            ->expects($this->exactly(4))
            ->method('dispatch')
            ->with($this->logicalOr(
                'resource_watcher.trackingId#1',
                'resource_watcher.trackingId#2',
                'resource_watcher.all'
            ), $this->logicalOr(
                $e1, $e2
            ));

        $watcher->start(1,1);
    }

    public function testTrackingFunctionally()
    {
        $file  = tempnam(sys_get_temp_dir(), 'sf2_resource_watcher_');
        $event = null;

        $watcher = new ResourceWatcher();
        $watcher->trackByListener($file, function($firedEvent) use(&$event) {
            $event = $firedEvent;
        });

        usleep(2000000);
        touch($file);

        $watcher->start(1,1);

        $this->assertNotNull($event);
        $this->assertSame($file, (string) $event->getResource());
        $this->assertSame(FilesystemEvent::MODIFY, $event->getType());

        $watcher->stop();

        unlink($file);

        $watcher->start(1,1);

        $this->assertNotNull($event);
        $this->assertSame($file, (string) $event->getResource());
        $this->assertSame(FilesystemEvent::DELETE, $event->getType());
    }

    protected function getResourceMock()
    {
        $resource = $this->getMock('Lurker\\Resource\\ResourceInterface');

        $resource
            ->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(true));

        return $resource;
    }

    protected function getFSEventMock()
    {
        return $this
            ->getMockBuilder('Lurker\\Event\\FilesystemEvent')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function getTrackedResourceMock($trackingId = null)
    {
        $resource = $this
            ->getMockBuilder('Lurker\\Resource\\TrackedResource')
            ->disableOriginalConstructor()
            ->getMock();

        if (null !== $trackingId) {
            $resource
                ->expects($this->any())
                ->method('getTrackingId')
                ->will($this->returnValue($trackingId));
        }

        return $resource;
    }
}
