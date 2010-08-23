<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Doctrine\ODM\MongoDB\SchemaManager;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class SchemaDropCommand extends AbstractSchemaCommand
{
    protected $_commandName = 'drop';

    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        $sm->dropDocumentCollection($document);
    }

    protected function processCollections(SchemaManager $sm)
    {
        $sm->dropCollections();
    }

    protected function processDocumentDB(SchemaManager $sm, $document)
    {
        $sm->dropDocumentDatabase($document);
    }

    protected function processDBs(SchemaManager $sm)
    {
        $sm->dropDatabases();
    }

}