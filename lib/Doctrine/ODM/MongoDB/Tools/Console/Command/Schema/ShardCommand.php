<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function sprintf;

class ShardCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('odm:schema:shard')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Document class to process (default: all classes)')
            ->setDescription('Enable sharding for selected documents');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getOption('class');

        $sm        = $this->getSchemaManager();
        $isErrored = false;

        try {
            if (isset($class)) {
                $this->processDocumentIndex($sm, $class);
                $output->writeln(sprintf('Enabled sharding for <info>%s</info>', $class));
            } else {
                $this->processIndex($sm);
                $output->writeln('Enabled sharding for <info>all classes</info>');
            }
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $isErrored = true;
        }

        return $isErrored ? 255 : 0;
    }

    protected function processDocumentIndex(SchemaManager $sm, string $document)
    {
        $sm->ensureDocumentSharding($document);
    }

    protected function processIndex(SchemaManager $sm)
    {
        $sm->ensureSharding();
    }

    /**
     * @throws BadMethodCallException
     */
    protected function processDocumentCollection(SchemaManager $sm, string $document)
    {
        throw new BadMethodCallException('Cannot update a document collection');
    }

    /**
     * @throws BadMethodCallException
     */
    protected function processCollection(SchemaManager $sm)
    {
        throw new BadMethodCallException('Cannot update a collection');
    }

    /**
     * @throws BadMethodCallException
     */
    protected function processDocumentDb(SchemaManager $sm, string $document)
    {
        throw new BadMethodCallException('Cannot update a document database');
    }

    /**
     * @throws BadMethodCallException
     */
    protected function processDb(SchemaManager $sm)
    {
        throw new BadMethodCallException('Cannot update a database');
    }
}
