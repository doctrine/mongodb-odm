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

class ShardCommand extends AbstractCommand
{
    use CommandCompatibility;

    /** @return void */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('odm:schema:shard')
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Document class to process (default: all classes)')
            ->setDescription('Enable sharding for selected documents');
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $class = $input->getOption('class');

        $sm        = $this->getSchemaManager();
        $isErrored = false;

        try {
            if (is_string($class)) {
                $this->processDocumentSharding($sm, $class, $this->getWriteConcernFromInput($input));
                $output->writeln(sprintf('Enabled sharding for <info>%s</info>', $class));
            } else {
                $this->processSharding($sm, $this->getWriteConcernFromInput($input));
                $output->writeln('Enabled sharding for <info>all classes</info>');
            }
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $isErrored = true;
        }

        return $isErrored ? 255 : 0;
    }

    /** @param class-string $document */
    private function processDocumentSharding(SchemaManager $sm, string $document, ?WriteConcern $writeConcern = null): void
    {
        $sm->ensureDocumentSharding($document, $writeConcern);
    }

    private function processSharding(SchemaManager $sm, ?WriteConcern $writeConcern = null): void
    {
        $sm->ensureSharding($writeConcern);
    }
}
