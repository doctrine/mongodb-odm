<?php

declare(strict_types=1);

require __DIR__ . '/cli-config.php';
use Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand;
use Symfony\Component\Console\Application;

$app = new Application('Doctrine MongoDB ODM');

if (isset($helperSet)) {
    $app->setHelperSet($helperSet);
}

$app->addCommands([
    new GenerateHydratorsCommand(),
    new GenerateProxiesCommand(),
    new QueryCommand(),
    new MetadataCommand(),
    new CreateCommand(),
    new DropCommand(),
    new UpdateCommand(),
]);

$app->run();
