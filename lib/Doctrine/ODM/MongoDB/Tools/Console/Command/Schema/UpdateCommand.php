<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

/**
 * @author Chris Jones <leeked@gmail.com>
 */
class UpdateCommand extends AbstractCommand
{
    /**
     * @var array
     */
    protected $availableOptions = array(parent::INDEXES);
    protected $commandName = 'update';

    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        echo 'processDocumentIndex' . PHP_EOL;
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->updateIndexes();
    }

    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        throw new \RuntimeException('Cannot update a document collection');
    }

    protected function processCollection(SchemaManager $sm)
    {
        throw new \RuntimeException('Cannot update a collection');
    }

    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        throw new \RuntimeException('Cannot update a document database');
    }

    protected function processDb(SchemaManager $sm)
    {
        throw new \RuntimeException('Cannot update a database');
    }
}