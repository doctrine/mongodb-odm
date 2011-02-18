Console Commands
================

Doctrine MongoDB ODM offers some console commands to ease your development process:

- ``mongodb:query`` - Query mongodb and inspect the outputted results from your document classes.
- ``mongodb:generate:documents`` - Generate document classes and method stubs from your mapping information.
- ``mongodb:generate:hydrators`` - Generates hydrator classes for document classes.
- ``mongodb:generate:proxies`` - Generates proxy classes for document classes.
- ``mongodb:generate:repositories`` -  Generate repository classes from your mapping information.
- ``mongodb:schema:create`` - Allows you to create databases, collections and indexes for your documents
- ``mongodb:schema:drop`` - Allows you to drop databases, collections and indexes for your documents

You can setup a console command easily with the following code. You just need an existing
``DocumentManager`` instance:

.. code-block:: php

    <?php

    // mongodb.php

    // ...

    $helpers = array(
        'dm' => new Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper($dm),
    );

    $helperSet = isset($helperSet) ? $helperSet : new \Symfony\Component\Console\Helper\HelperSet();
    foreach ($helpers as $name => $helper) {
        $helperSet->set($helper, $name);
    }

    $cli = new \Symfony\Component\Console\Application('Doctrine ODM MongoDB Command Line Interface', Doctrine\ODM\MongoDB\Version::VERSION);
    $cli->setCatchExceptions(true);
    $cli->setHelperSet($helperSet);
    $cli->addCommands(array(
        new \Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand(),
        new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateDocumentsCommand(),
        new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateRepositoriesCommand(),
        new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand(),
        new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand(),
        new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand(),
        new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand(),
    ));
    $cli->run();

Now you can run commands like the following:

    $ php mongodb.php mongodb:query User "{ username : 'jwage' }"

The above would output the results from the query.