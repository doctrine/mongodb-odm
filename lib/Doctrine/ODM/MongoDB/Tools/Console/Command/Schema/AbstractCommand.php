<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Command\Command;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    const DB = 'db';
    const COLLECTION = 'collection';
    const INDEX = 'index';

    abstract protected function processDocumentCollection(SchemaManager $sm, $document);
    abstract protected function processCollection(SchemaManager $sm);
    abstract protected function processDocumentDb(SchemaManager $sm, $document);
    abstract protected function processDb(SchemaManager $sm);
    abstract protected function processDocumentIndex(SchemaManager $sm, $document);
    abstract protected function processIndex(SchemaManager $sm);

    /**
     * @return Doctrine\ODM\MongoDB\SchemaManager
     */
    protected function getSchemaManager()
    {
        return $this->getDocumentManager()->getSchemaManager();
    }

    /**
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->getHelper('documentManager')->getDocumentManager();
    }

    /**
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->getDocumentManager()->getMetadataFactory();
    }
}
