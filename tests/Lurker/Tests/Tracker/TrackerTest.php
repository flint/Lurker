<?php

namespace Lurker\Tests\Tracker;

use Lurker\Event\FilesystemEvent;
use Lurker\Resource\TrackedResource;
use Lurker\Tracker\TrackerInterface;
use Lurker\Resource\DirectoryResource;
use Lurker\Resource\FileResource;

abstract class TrackerTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpDir;

    public function setUp()
    {
        $this->tmpDir = sys_get_temp_dir().'/sf2_resource_watcher_tests';
        if (is_dir($this->tmpDir)) {
            $this->cleanDir($this->tmpDir);
        }

        mkdir($this->tmpDir);

        $this->tmpDir = realpath($this->tmpDir);
    }

    public function tearDown()
    {
        $this->cleanDir($this->tmpDir);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDoesNotTrackMissingFiles()
    {
        $tracker = $this->getTracker();
        $tracker->track(new TrackedResource('missing', new FileResource(__DIR__ . '/missingfile')));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDoesNotTrackMissingDirectories()
    {
        $tracker = $this->getTracker();

        $tracker->track(new TrackedResource('missing', new DirectoryResource(__DIR__.'/missingdir')));
    }

    public function testDeleteResourceAndCreateDifferentOne()
    {
        $tracker = $this->getTracker();

        mkdir($dir = $this->tmpDir.'/dir');
        touch($file = $dir.'/file');

        $tracker->track(new TrackedResource('dir', $resource = new DirectoryResource($dir)));

        unlink($file);
        mkdir($file);
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(2, $events);

        $this->assertHasResourceEvent($file, FilesystemEvent::DELETE, $events);
        $this->assertHasResourceEvent($file, FilesystemEvent::CREATE, $events);
    }

    public function testTrackSimpleFileChanges()
    {
        $tracker = $this->getTracker();

        touch($file = $this->tmpDir.'/foo');

        $tracker->track(new TrackedResource('foo', $resource = new FileResource($file)));

        $this->sleep();
        touch($file);

        $events = $tracker->getEvents();
        $this->assertCount(1, $events);
        $this->assertHasResourceEvent($file, FilesystemEvent::MODIFY, $events);

        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(0, $events);

        $this->sleep();
        unlink($file);

        $events = $tracker->getEvents();
        $this->assertCount(1, $events);
        $this->assertHasResourceEvent($file, FilesystemEvent::DELETE, $events);

        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(0, $events);
    }

    public function testTrackSimpleDirChanges()
    {
        $tracker = $this->getTracker();

        mkdir($directory = $this->tmpDir.'/bar');

        $tracker->track(new TrackedResource('bar', $resource = new DirectoryResource($directory)));

        touch($file1 = $directory.'/new_file');
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(1, $events);
        $this->assertHasResourceEvent($file1, FilesystemEvent::CREATE, $events);

        touch($file2 = $directory.'/new_file2');
        touch($file3 = $directory.'/new_file3');
        touch($file1);
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(3, $events);

        $this->assertHasResourceEvent($file1, FilesystemEvent::MODIFY, $events);
        $this->assertHasResourceEvent($file2, FilesystemEvent::CREATE, $events);
        $this->assertHasResourceEvent($file3, FilesystemEvent::CREATE, $events);

        unlink($file1);
        unlink($file3);
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(2, $events);

        $this->assertHasResourceEvent($file1, FilesystemEvent::DELETE, $events);
        $this->assertHasResourceEvent($file3, FilesystemEvent::DELETE, $events);

        unlink($file2);
        rmdir($directory);
        touch($directory);
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(2, $events);
        $this->assertHasResourceEvent($file2, FilesystemEvent::DELETE, $events);
        $this->assertHasResourceEvent($directory, FilesystemEvent::DELETE, $events);
    }

    public function testTrackDeepDirChanges()
    {
        $tracker = $this->getTracker();

        mkdir($directory1 = $this->tmpDir.'/bar2');

        $tracker->track(
            new TrackedResource('bar2', $resource = new DirectoryResource($directory1))
        );

        mkdir($directory2 = $directory1.'/subdir');
        touch($file1 = $directory2.'/sub_file');
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(2, $events);

        $this->assertHasResourceEvent($directory2, FilesystemEvent::CREATE, $events);
        $this->assertHasResourceEvent($file1, FilesystemEvent::CREATE, $events);

        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(0, $events);

        touch($file2 = $directory1.'/dir1_file.txt');
        touch($file3 = $directory2.'/dir2_file.txt');
        touch($file1);
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(3, $events);

        $this->assertHasResourceEvent($file1, FilesystemEvent::MODIFY, $events);
        $this->assertHasResourceEvent($file2, FilesystemEvent::CREATE, $events);
        $this->assertHasResourceEvent($file3, FilesystemEvent::CREATE, $events);

        $this->cleanDir($directory2);
        touch($file2);
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(4, $events);

        $this->assertHasResourceEvent($directory2, FilesystemEvent::DELETE, $events);
        $this->assertHasResourceEvent($file1, FilesystemEvent::DELETE, $events);
        $this->assertHasResourceEvent($file3, FilesystemEvent::DELETE, $events);
        $this->assertHasResourceEvent($file2, FilesystemEvent::MODIFY, $events);
    }

    public function testTrackFilteredDirectory()
    {
        $tracker = $this->getTracker();

        mkdir($directory1 = $this->tmpDir.'/bar3');
        mkdir($directory2 = $directory1.'/subdir');
        touch($file1 = $directory2.'/sub_file.txt');

        $tracker->track(
            new TrackedResource('bar3',
                $resource = new DirectoryResource($directory1, '/\.txt$/')
            )
        );
        $this->sleep();

        touch($file1);
        // this file creation should not be notified as it doesn't
        // fulfill the directory resource pattern requirement:
        touch($file2 = $directory1.'/dir1_file');
        touch($file3 = $directory2.'/dir2_file.txt');
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(2, $events);
        $this->assertHasResourceEvent($file1, FilesystemEvent::MODIFY, $events);
        $this->assertHasResourceEvent($file3, FilesystemEvent::CREATE, $events);
    }

    public function testTrackSpecificEvents()
    {
        $tracker = $this->getTracker();

        mkdir($directory1 = $this->tmpDir.'/bar3');
        mkdir($directory2 = $directory1.'/subdir');
        touch($file1 = $directory2.'/sub_file.txt');
        touch($file3 = $directory2.'/dir2_file.txt');

        $tracker->track(
            new TrackedResource('bar3',
                $resource = new DirectoryResource($directory1, '/\.txt$/')
            ), FilesystemEvent::MODIFY | FilesystemEvent::DELETE
        );
        $this->sleep();

        touch($file1);
        unlink($file3);
        $this->sleep();

        $events = $tracker->getEvents();
        $this->assertCount(2, $events);
        $this->assertHasResourceEvent($file1, FilesystemEvent::MODIFY, $events);
        $this->assertHasResourceEvent($file3, FilesystemEvent::DELETE, $events);
    }

    protected function assertHasResourceEvent($resource, $type, array $events)
    {
        $result = array();
        foreach ($events as $event) {
            if ($resource === (string) $event->getResource()->getResource()) {
                $result[] = $event->getType();
            }
        }

        $types = array(
            1 => 'CREATE',
            2 => 'MODIFY',
            4 => 'DELETE',
        );

        if ($result) {
            return $this->assertTrue(in_array($type, $result), sprintf('Expected event: %s, actual: %s ', $types[$type], implode(' or ', array_intersect_key($types, array_flip($result)))));
        }

        $this->fail(sprintf('Can not find "%s" change event', $resource));
    }

    protected function sleep()
    {
        usleep($this->getMinimumInterval());
    }

    abstract protected function getMinimumInterval();

    /**
     * @return TrackerInterface
     */
    abstract protected function getTracker();

    protected function cleanDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $flags = \FilesystemIterator::SKIP_DOTS;
        $iterator = new \RecursiveDirectoryIterator($dir, $flags);
        $iterator = new \RecursiveIteratorIterator(
            $iterator, \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                rmdir((string) $path);
            } else {
                unlink((string) $path);
            }
        }

        rmdir($dir);
    }
}
