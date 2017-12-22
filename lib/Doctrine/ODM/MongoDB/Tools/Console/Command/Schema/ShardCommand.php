<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShardCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('odm:schema:shard')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Document class to process (default: all classes)')
            ->setDescription('Enable sharding for selected documents')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getOption('class');

        $sm = $this->getSchemaManager();
        $isErrored = false;

        try {
            if (isset($class)) {
                $this->processDocumentIndex($sm, $class);
                $output->writeln(sprintf('Enabled sharding for <info>%s</info>', $class));
            } else {
                $this->processIndex($sm);
                $output->writeln('Enabled sharding for <info>all classes</info>');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $isErrored = true;
        }

        return $isErrored ? 255 : 0;
    }

    /**
     * @param SchemaManager $sm
     * @param object $document
     */
    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->ensureDocumentSharding($document);
    }

    /**
     * @param SchemaManager $sm
     */
    protected function processIndex(SchemaManager $sm)
    {
        $sm->ensureSharding();
    }

    /**
     * @param SchemaManager $sm
     * @param object $document
     * @throws \BadMethodCallException
     */
    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        throw new \BadMethodCallException('Cannot update a document collection');
    }

    /**
     * @param SchemaManager $sm
     * @throws \BadMethodCallException
     */
    protected function processCollection(SchemaManager $sm)
    {
        throw new \BadMethodCallException('Cannot update a collection');
    }

    /**
     * @param SchemaManager $sm
     * @param object $document
     * @throws \BadMethodCallException
     */
    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        throw new \BadMethodCallException('Cannot update a document database');
    }

    /**
     * @param SchemaManager $sm
     * @throws \BadMethodCallException
     */
    protected function processDb(SchemaManager $sm)
    {
        throw new \BadMethodCallException('Cannot update a database');
    }
}
