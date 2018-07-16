<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput as ConsoleOutput;

$file = __DIR__.'/../../../autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies using Composer to run the test suite.');
}
$autoload = require $file;

AnnotationRegistry::registerLoader(function ($class) use ($autoload) {
    $autoload->loadClass($class);

    return class_exists($class, false);
});

include __DIR__.'/../../../../src/Kernel.php';
$application = new Application(new \App\Kernel('dev', true));
$application->setAutoExit(false);

// Create database schema.
$input = new ArrayInput(array('command' => 'doctrine:schema:create'));
$application->run($input, new ConsoleOutput());
