<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ODM\MongoDB\Tools\Console\Command\CommandCompatibility;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function is_string;
use function sprintf;

class UpdateCommand extends AbstractCommand
{
    use CommandCompatibility;

    /** @return void */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('odm:schema:update')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Document class to process (default: all classes)')
            ->addOption('skip-search-indexes', null, InputOption::VALUE_NONE, 'Skip processing of search indexes')
            ->addOption('disable-validators', null, InputOption::VALUE_NONE, 'Do not update database-level validation rules')
            ->setDescription('Update indexes and validation rules for your documents');
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $class               = $input->getOption('class');
        $updateValidators    = ! $input->getOption('disable-validators');
        $updateSearchIndexes = ! $input->getOption('skip-search-indexes');

        $sm        = $this->getSchemaManager();
        $isErrored = false;

        try {
            if (is_string($class)) {
                $this->processDocumentIndex($sm, $class, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                $output->writeln(sprintf('Updated <comment>index(es)</comment> for <info>%s</info>', $class));

                if ($updateValidators) {
                    $this->processDocumentValidator($sm, $class, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                    $output->writeln(sprintf('Updated <comment>validation</comment> for <info>%s</info>', $class));
                }

                if ($updateSearchIndexes) {
                    $this->processDocumentSearchIndex($sm, $class);
                    $output->writeln(sprintf('Updated <comment>search index(es)</comment> for <info>%s</info>', $class));
                }
            } else {
                $this->processIndex($sm, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                $output->writeln('Updated <comment>indexes</comment> for <info>all classes</info>');

                if ($updateValidators) {
                    $this->processValidators($sm, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                    $output->writeln('Updated <comment>validation</comment> for <info>all classes</info>');
                }

                if ($updateSearchIndexes) {
                    $this->processSearchIndex($sm);
                    $output->writeln('Updated <comment>search indexes</comment> for <info>all classes</info>');
                }
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

    /** @return void */
    protected function processDocumentValidator(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->updateDocumentValidator($document, $maxTimeMs, $writeConcern);
    }

    /** @return void */
    protected function processValidators(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->updateValidators($maxTimeMs, $writeConcern);
    }

    protected function processDocumentSearchIndex(SchemaManager $sm, string $document): void
    {
        $sm->updateDocumentSearchIndexes($document);
    }

    protected function processSearchIndex(SchemaManager $sm): void
    {
        $sm->updateSearchIndexes();
    }
}
