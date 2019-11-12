Console Commands
================

Doctrine MongoDB ODM offers some console commands, which utilize Symfony2's
Console component, to ease your development process:

- ``odm:clear-cache:metadata`` - Clear all metadata cache of the various cache drivers.
- ``odm:query`` - Query mongodb and inspect the outputted results from your document classes.
- ``odm:generate:hydrators`` - Generates hydrator classes for document classes.
- ``odm:generate:proxies`` - Generates proxy classes for document classes.
- ``odm:schema:create`` - Allows you to create databases, collections and indexes for your documents
- ``odm:schema:drop`` - Allows you to drop databases, collections and indexes for your documents
- ``odm:schema:update`` - Allows you to update indexes for your documents
- ``odm:schema:shard`` - Allows you to enable sharding for your documents

Provided you have an existing ``DocumentManager`` instance, you can setup a
console command easily with the following code:

.. code-block:: php

    <?php

    // mongodb.php

    // ... include Composer autoloader and configure DocumentManager instance

    $helperSet = new \Symfony\Component\Console\Helper\HelperSet(
        [
            'dm' => new \Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper($dm),
        ]
    );

    $app = new Application('Doctrine MongoDB ODM');
    $app->setHelperSet($helperSet);
    $app->addCommands(
        [
            new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand(),
            new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand(),
            new \Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand(),
            new \Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand(),
            new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand(),
            new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand(),
            new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand(),
            new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\ShardCommand(),
        ]
    );

    $app->run();

A reference implementation of the console command may be found in the
``tools/sandbox`` directory of the project repository. That command is
configured to store generated hydrators and proxies in the same directory, and
relies on the main project's Composer dependencies. You will want to customize
its configuration files if you intend to use it in your own project.
