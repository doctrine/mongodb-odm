<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class DropCommand extends AbstractCommand
{

    protected $_commandName = 'drop';

    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        $sm->dropDocumentCollection($document);
    }

    protected function processCollection(SchemaManager $sm)
    {
        $sm->dropCollections();
    }

    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        $sm->dropDocumentDatabase($document);
    }

    protected function processDb(SchemaManager $sm)
    {
        $sm->dropDatabases();
    }

    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->deleteDocumentIndexes($document);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->deleteIndexes();
    }
    protected function processDocumentProxy(SchemaManager $sm, $document)
    {
        throw new \Exception("Cannot delete proxy files");
    }

    protected function processProxy(SchemaManager $sm)
    {
        throw new \Exception("Cannot delete proxy files");
    }

}