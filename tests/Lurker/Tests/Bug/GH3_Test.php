<?php

namespace Lurker\Tests\Bug;

use Lurker\Resource\TrackedResource;
use Lurker\Resource\DirectoryResource;
use Lurker\Event\FilesystemEvent;

use Lurker\Tests\Tracker\InotifyTrackerTest;


class GH3_Test extends InotifyTrackerTest
{
    public function testMovedFileTriggersCreateOrDeleteEvent()
    {
        mkdir($dir = $this->tmpDir.'/test/foo', 0777, true);
        touch($this->tmpDir.'/test/foo/bar.txt');
        $expected = array(
            //['type' => FilesystemEvent::CREATE, 'file' => $this->tmpDir.'/test/foo/bar.txt'],
            array('type' => FilesystemEvent::DELETE, 'file' => $this->tmpDir.'/test/foo/bar.txt'),
            array('type' => FilesystemEvent::CREATE, 'file' => $this->tmpDir.'/test/foo/bar.txt'),
        );

        $tracker = $this->getTracker();
        $tracker->track(new TrackedResource('foo', $resource = new DirectoryResource($dir)));

        usleep(200000);
        rename($this->tmpDir.'/test/foo/bar.txt', $this->tmpDir.'/test/bar.txt');
        rename($this->tmpDir.'/test/bar.txt', $this->tmpDir.'/test/foo/bar.txt');
        usleep(200000);
        
        $events = $tracker->getEvents();
        foreach ($expected as $exp) {
            $this->assertHasResourceEvent($exp['file'], $exp['type'], $events);
        }
    }
    
}

