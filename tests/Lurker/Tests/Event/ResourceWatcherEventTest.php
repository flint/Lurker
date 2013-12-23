<?php

namespace Lurker\Tests\Event;

use Lurker\Event\ResourceWatcherEvent;

class ResourceWatcherEventTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructAndGetters()
    {
        $watcher = $this->getMockBuilder('Lurker\ResourceWatcher')
            ->disableOriginalCOnstructor()
            ->getMock();
        $event = new ResourceWatcherEvent($watcher);

        $this->assertSame($watcher, $event->getResourceWatcher());
    }
}
