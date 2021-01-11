<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_filter;
use function is_string;
use function sprintf;
use function ucfirst;

class DropCommand extends AbstractCommand
{
    /** @var string[] */
    private $dropOrder = [self::INDEX, self::COLLECTION, self::DB];

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('odm:schema:drop')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption(self::DB, null, InputOption::VALUE_NONE, 'Drop databases')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Drop collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Drop indexes')
            ->setDescription('Drop databases, collections and indexes for your documents');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drop = array_filter($this->dropOrder, static function ($option) use ($input) {
            return $input->getOption($option);
        });

        // Default to the full drop order if no options were specified
        $drop = empty($drop) ? $this->dropOrder : $drop;

        $class     = $input->getOption('class');
        $sm        = $this->getSchemaManager();
        $isErrored = false;

        foreach ($drop as $option) {
            try {
                if (is_string($class)) {
                    $this->{'processDocument' . ucfirst($option)}($sm, $class, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                } else {
                    $this->{'process' . ucfirst($option)}($sm, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                }

                $output->writeln(sprintf(
                    'Dropped <comment>%s%s</comment> for <info>%s</info>',
                    $option,
                    is_string($class) ? ($option === self::INDEX ? '(es)' : '') : ($option === self::INDEX ? 'es' : 's'),
                    is_string($class) ? $class : 'all classes'
                ));
            } catch (Throwable $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $isErrored = true;
            }
        }

        return $isErrored ? 255 : 0;
    }

    protected function processDocumentCollection(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->dropDocumentCollection($document, $maxTimeMs, $writeConcern);
    }

    protected function processCollection(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->dropCollections($maxTimeMs, $writeConcern);
    }

    protected function processDocumentDb(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->dropDocumentDatabase($document, $maxTimeMs, $writeConcern);
    }

    protected function processDb(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->dropDatabases($maxTimeMs, $writeConcern);
    }

    protected function processDocumentIndex(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->deleteDocumentIndexes($document, $maxTimeMs, $writeConcern);
    }

    protected function processIndex(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        $sm->deleteIndexes($maxTimeMs, $writeConcern);
    }
}
