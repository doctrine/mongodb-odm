<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Chris Jones <leeked@gmail.com>
 */
class UpdateCommand extends AbstractCommand
{
    private $dropOrder = array(self::INDEX, self::COLLECTION, self::DB);

    protected function configure()
    {
        $this
            ->setName('odm:schema:update')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Document class to process (default: all classes)')
            ->setDescription('Update indexes for your documents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getOption('class');
        $sm = $this->getSchemaManager();

        try {
            if (isset($class)) {
                $this->processDocumentIndex($sm, $class);
                $output->writeln(sprintf('Updated <comment>index(es)</comment> for <info>%s</info>', $class));
            } else {
                $this->processIndex($sm);
                $output->writeln('Updated <comment>indexes</comment> for <info>all classes</info>');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    protected function processDocumentIndex(SchemaManager $sm, $document)
    {
        $sm->updateDocumentIndexes($document);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->updateIndexes();
    }

    protected function processDocumentCollection(SchemaManager $sm, $document)
    {
        throw new \BadMethodCallException('Cannot update a document collection');
    }

    protected function processCollection(SchemaManager $sm)
    {
        throw new \BadMethodCallException('Cannot update a collection');
    }

    protected function processDocumentDb(SchemaManager $sm, $document)
    {
        throw new \BadMethodCallException('Cannot update a document database');
    }

    protected function processDb(SchemaManager $sm)
    {
        throw new \BadMethodCallException('Cannot update a database');
    }
}
