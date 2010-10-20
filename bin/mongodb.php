<?php

$vendorDir = __DIR__.'/../lib/vendor';
require_once $vendorDir.'/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', dirname($vendorDir));
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Symfony', $vendorDir );
$classLoader->register();

$configFile = getcwd() . DIRECTORY_SEPARATOR . 'cli-config.php';

$helperSet = null;
if (file_exists($configFile)) {
    if ( ! is_readable($configFile)) {
        trigger_error(
            'Configuration file [' . $configFile . '] does not have read permission.', E_ERROR
        );
    }

    require $configFile;

    foreach ($GLOBALS as $helperSetCandidate) {
        if ($helperSetCandidate instanceof \Symfony\Component\Console\Helper\HelperSet) {
            $helperSet = $helperSetCandidate;
            break;
        }
    }
}

$helperSet = ($helperSet) ?: new \Symfony\Component\Console\Helper\HelperSet();

$cli = new \Symfony\Component\Console\Application('Doctrine ODM MongoDB Command Line Interface', Doctrine\ODM\MongoDB\Version::VERSION);
$cli->setCatchExceptions(true);
$cli->setHelperSet($helperSet);
$cli->addCommands(array(
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand(),
));
$cli->run();