Lurker
======

This is a continuation of Konstantin Kudryashov's ResourceWatcher for Symfony2. It have been moved
from his fork and renamed Lurker to allow a wider adoption.

[![Build Status](https://travis-ci.org/henrikbjorn/Lurker.png?branch=master)](https://travis-ci.org/henrikbjorn/Lurker)

Getting Started
---------------

Use composer to install it by adding the following to your `composer.json` file.

``` json
{
    "require" : {
        "henrikbjorn/lurker" : "1.0.*@dev"
    }
}
```

And then run `composer update henrikbjorn/lurker` to get the package installed.

### Tracking Resources

Lurker works by giving the resource watcher a tracking id which is the name of the event and a path to
the resource you want to track.

When all the resources have been added that should be track you would want to add event listeners for them so
your can act when the resources are changed.

``` php
<?php

use Lurker\Event\FilesystemEvent;
use Lurker\ResourceWatcher;

$watcher = new ResourceWatcher;
$watcher->track('twig.templates', '/path/to/views');

$watcher->addListener('twig.templates', function (FilesystemEvent $event) {
    echo $event->getResource() . 'was' . $event->getTypeString();
});

$watcher->start();
```

The above example would watch for all events `create`, `delete` and `modify`. This can be controlled by passing a 
third parameter to `track()`.

``` php
<?php

$watcher->track('twig.templates', '/path/to/views', FilesystemEvent::CREATE);
$watcher->track('twig.templates', '/path/to/views', FilesystemEvent::MODIFY);
$watcher->track('twig.templates', '/path/to/views', FilesystemEvent::DELETE);
$watcher->track('twig.templates', '/path/to/views', FilesystemEvent::ALL);
```

Note that `FilesystemEvent::ALL` is a special case and of course means it will watch for every type of event.

Special Thanks
--------------

* [Konstantin Kudryashov](http://twitter.com/everzet) for the original code.
