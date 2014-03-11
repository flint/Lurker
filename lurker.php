#!/usr/bin/env php
<?php
// installed via composer?
if (file_exists($a = __DIR__ . '/../../autoload.php')) {
    require_once $a;
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lurker\ResourceWatcher;

$console = new Application();

$console->register('track')
        ->addArgument('path')
        ->setCode(function(InputInterface $input, OutputInterface $output) {
            $config = $input->getArgument('path');
            if ($config && !is_file($config))
                throw new Exception('File not exists');

            $watcher = require $config;
            if(!$watcher instanceof ResourceWatcher) throw new Exception ('Given configurationfile does not retur instance of Lurker/ResrouceWatcher');
            while (true) {
                $output->writeln("Start watching...");
                $watcher->start();
                sleep(1);
            }
        });

$console->run();
