<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    public const DB = 'db';
    public const COLLECTION = 'collection';
    public const INDEX = 'index';

    abstract protected function processDocumentCollection(SchemaManager $sm, $document);

    abstract protected function processCollection(SchemaManager $sm);

    abstract protected function processDocumentDb(SchemaManager $sm, $document);

    abstract protected function processDb(SchemaManager $sm);

    abstract protected function processDocumentIndex(SchemaManager $sm, $document);

    abstract protected function processIndex(SchemaManager $sm);

    /**
     * @return SchemaManager
     */
    protected function getSchemaManager()
    {
        return $this->getDocumentManager()->getSchemaManager();
    }

    /**
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->getHelper('documentManager')->getDocumentManager();
    }

    /**
     * @return ClassMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->getDocumentManager()->getMetadataFactory();
    }
}
