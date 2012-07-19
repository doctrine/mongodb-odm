<?php

if (!file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require_once $file;

$loader->add('Doctrine\ODM\MongoDB\Tests', __DIR__ . '/../tests');
$loader->add('Documents', __DIR__);
$loader->add('Stubs', __DIR__);

\Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver::registerAnnotationClasses();

/* Driver version 1.2.11 deprecated setSlaveOkay() in anticipation of connection
 * read preferences. Ignore these warnings until read preferences are
 * implemented.
 */
if (0 >= version_compare('1.2.11', \Mongo::VERSION)) {
    error_reporting(error_reporting() ^ E_DEPRECATED);
}
