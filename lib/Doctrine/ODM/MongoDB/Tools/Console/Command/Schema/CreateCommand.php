<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class CreateCommand extends AbstractCommand
{
    protected $commandName = 'create';

    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        $sm->createDocumentCollection($document);
    }

    protected function processCollection(SchemaManager $sm)
    {
        $sm->createCollections();
    }

    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        $sm->createDocumentDatabase($document);
    }

    protected function processDb(SchemaManager $sm)
    {
        $sm->createDatabases();
    }

    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->ensureDocumentIndexes($document);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->ensureIndexes();
    }

    protected function processDocumentProxy(SchemaManager $sm, $document)
    {
        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses(array($this->getMetadataFactory()->getMetadataFor($document)));
    }

    protected function processProxy(SchemaManager $sm)
    {
        $this->getDocumentManager()->getProxyFactory()->generateProxyClasses($this->getMetadataFactory()->getAllMetadata());
    }

    /**
     * Creation of schema should happen in direct order (dbs -> collections -> indexes)
     *
     * @param Input\InputInterface $input
     * @param Output\OutputInterface $output
     */
    protected function initialize(Input\InputInterface $input, Output\OutputInterface $output)
    {
        $this->availableOptions = array_reverse($this->availableOptions);
    }
}