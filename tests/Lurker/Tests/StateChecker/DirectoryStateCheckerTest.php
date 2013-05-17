<?php

namespace Lurker\Tests\StateChecker;

use Lurker\Event\FilesystemEvent;
use Lurker\StateChecker\DirectoryStateChecker;
use Lurker\Resource\ResourceInterface;
use Lurker\Resource\DirectoryResource;
use Lurker\Resource\FileResource;

class DirectoryStateCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testDeepFileChanged()
    {
        $resource = $this->createDirectoryResourceMock();
        $resource
            ->expects($this->any())
            ->method('getFilteredResources')
            ->will($this->returnValue(array(
                $foo = $this->createDirectoryResourceMock()
            )));

        $resource
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(11));

        $foo
            ->expects($this->any())
            ->method('getFilteredResources')
            ->will($this->returnValue(array(
                $foobar = $this->createFileResourceMock()
            )));
        $foo
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(22));

        $foobar
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(33));

        $checker = new DirectoryStateChecker($resource);

        $this->touchResource($resource, true, true);
        $this->touchResource($foo,      true, true);
        $this->touchResource($foobar,   true, false);

        $this->assertEquals(array(
            array('event' => FilesystemEvent::MODIFY, 'resource' => $foobar)
        ), $checker->getChangeset());
    }

    public function testDeepFileDeleted()
    {
        $resource = $this->createDirectoryResourceMock();
        $resource
            ->expects($this->any())
            ->method('getFilteredResources')
            ->will($this->returnValue(array(
                $foo = $this->createDirectoryResourceMock()
            )));
        $resource
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(11));
        $foo
            ->expects($this->any())
            ->method('getFilteredResources')
            ->will($this->onConsecutiveCalls(
                array($foobar = $this->createFileResourceMock(array(true, false))),
                array()
            ));
        $foo
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(22));
        $foobar
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(33));

        $checker = new DirectoryStateChecker($resource);

        $this->touchResource($resource, true, true);
        $this->touchResource($foo,      true, true);

        $this->assertEquals(array(
            array('event' => FilesystemEvent::DELETE, 'resource' => $foobar)
        ), $checker->getChangeset());
    }

    public function testDeepFileCreated()
    {
        $resource = $this->createDirectoryResourceMock();
        $resource
            ->expects($this->any())
            ->method('getFilteredResources')
            ->will($this->returnValue(array(
                $foo = $this->createDirectoryResourceMock()
            )));
        $resource
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(11));
        $foo
            ->expects($this->any())
            ->method('getFilteredResources')
            ->will($this->onConsecutiveCalls(
                array(),
                array($foobar = $this->createFileResourceMock())
            ));
        $foo
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(22));
        $foobar
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(33));

        $checker = new DirectoryStateChecker($resource);

        $this->touchResource($resource, true, true);
        $this->touchResource($foo,      true, true);

        $this->assertEquals(array(
            array('event' => FilesystemEvent::CREATE, 'resource' => $foobar)
        ), $checker->getChangeset());
    }

    protected function touchResource(ResourceInterface $resource, $exists = true, $fresh = true)
    {
        if ($exists) {
            $resource
                ->expects($this->any())
                ->method('isFresh')
                ->will($this->returnValue($fresh));
        } else {
            $resource
                ->expects($this->any())
                ->method('exists')
                ->will($this->returnValue(false));
        }
    }

    protected function createDirectoryResourceMock($exists = true)
    {
        $resource = $this->getMockBuilder('Lurker\Resource\DirectoryResource')
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResourceExists($resource, $exists);

        return $resource;
    }

    protected function createFileResourceMock($exists = true)
    {
        $resource = $this->getMockBuilder('Lurker\Resource\FileResource')
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResourceExists($resource, $exists);

        return $resource;
    }

    protected function setResourceExists($resource, $exists)
    {
        if (is_array($exists)) {
            $resource
                ->expects($this->any())
                ->method('exists')
                ->will(call_user_func_array(array($this, 'onConsecutiveCalls'), $exists));
        } else {
            $resource
                ->expects($this->any())
                ->method('exists')
                ->will($this->returnValue($exists));
        }
    }
}
