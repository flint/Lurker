<?php

namespace Lurker\Tests\StateChecker;

use Lurker\Event\FilesystemEvent;
use Lurker\StateChecker\FileStateChecker;

class FileStateCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResource()
    {
        $resource = $this->createResource();
        $checker = $this->createChecker($resource);

        $this->assertSame($resource, $checker->getResource());
    }

    public function testNoChanges()
    {
        $resource = $this->createResource(true);
        $checker = $this->createChecker($resource);

        $resource
            ->expects($this->once())
            ->method('isFresh')
            ->with(12)
            ->will($this->returnValue(true));

        $this->assertEquals(array(), $checker->getChangeset());
    }

    public function testDeleted()
    {
        $resource = $this->createResource(null);
        $resource
            ->expects($this->any())
            ->method('exists')
            ->will($this->onConsecutiveCalls(true, false));

        $checker = $this->createChecker($resource);

        $this->assertEquals(
            array(array('event' => FilesystemEvent::DELETE, 'resource' => $resource)),
            $checker->getChangeset()
        );
    }

    public function testModified()
    {
        $resource = $this->createResource(true);
        $checker = $this->createChecker($resource);

        $resource
            ->expects($this->once())
            ->method('isFresh')
            ->with(12)
            ->will($this->returnValue(false));

        $this->assertEquals(
            array(array('event' => FilesystemEvent::MODIFY, 'resource' => $resource)),
            $checker->getChangeset()
        );
    }

    public function testConsecutiveChecks()
    {
        $resource = $this->createResource(null);
        $resource
            ->expects($this->any())
            ->method('exists')
            ->will($this->onConsecutiveCalls(true, true, false));
        $checker = $this->createChecker($resource);

        $resource
            ->expects($this->once())
            ->method('isFresh')
            ->with(12)
            ->will($this->returnValue(false));

        $this->assertEquals(
            array(array('event' => FilesystemEvent::MODIFY, 'resource' => $resource)),
            $checker->getChangeset()
        );

        $this->assertEquals(
            array(array('event' => FilesystemEvent::DELETE, 'resource' => $resource)),
            $checker->getChangeset()
        );

        $this->assertEquals(array(), $checker->getChangeset());
    }

    protected function createResource($exists = true)
    {
        $resource = $this
            ->getMockBuilder('Lurker\Resource\FileResource')
            ->disableOriginalConstructor()
            ->getMock();

        $resource
            ->expects($this->any())
            ->method('getModificationTime')
            ->will($this->returnValue(11));

        if (null !== $exists) {
            $resource
                ->expects($this->any())
                ->method('exists')
                ->will($this->returnValue($exists));
        }

        return $resource;
    }

    protected function createChecker($resource)
    {
        return new FileStateChecker($resource);
    }
}
