<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\SchemaManager;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function is_string;
use function sprintf;

class UpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('odm:schema:update')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Document class to process (default: all classes)')
            ->setDescription('Update indexes for your documents');
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
            if (is_string($class)) {
                $this->processDocumentIndex($sm, $class, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                $output->writeln(sprintf('Updated <comment>index(es)</comment> for <info>%s</info>', $class));
            } else {
                $this->processIndex($sm, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                $output->writeln('Updated <comment>indexes</comment> for <info>all classes</info>');
            }
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $isErrored = true;
        }

        return $isErrored ? 255 : 0;
    }

    protected function processDocumentIndex(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->updateDocumentIndexes($document, $maxTimeMs, $writeConcern);
    }

    protected function processIndex(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->updateIndexes($maxTimeMs, $writeConcern);
    }

    /**
     * @throws BadMethodCallException
     */
    protected function processDocumentCollection(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('Cannot update a document collection');
    }

    /**
     * @throws BadMethodCallException
     */
    protected function processCollection(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('Cannot update a collection');
    }

    /**
     * @throws BadMethodCallException
     */
    protected function processDocumentDb(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('Cannot update a document database');
    }

    /**
     * @throws BadMethodCallException
     */
    protected function processDb(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('Cannot update a database');
    }
}
