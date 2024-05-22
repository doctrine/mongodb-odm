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

use function array_filter;
use function is_string;
use function sprintf;

class DropCommand extends AbstractCommand
{
    use CommandCompatibility;

    /** @var string[] */
    private array $dropOrder = [self::SEARCH_INDEX, self::INDEX, self::COLLECTION, self::DB];

    /* @var array<string, list<string>> */
    private const INFLECTIONS = [
        self::DB => ['database', 'databases'],
        self::COLLECTION => ['collection', 'collections'],
        self::INDEX => ['index(es)', 'indexes'],
        self::SEARCH_INDEX => ['search index(es)', 'search indexes'],
    ];

    /** @return void */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('odm:schema:drop')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption(self::DB, null, InputOption::VALUE_NONE, 'Drop databases')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Drop collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Drop indexes')
            ->addOption(self::SEARCH_INDEX, null, InputOption::VALUE_NONE, 'Drop search indexes')
            ->addOption('skip-search-indexes', null, InputOption::VALUE_NONE, 'Skip processing of search indexes')
            ->setDescription('Drop databases, collections and indexes for your documents');
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $drop = array_filter($this->dropOrder, static fn (string $option): bool => (bool) $input->getOption($option));

        // Default to the full drop order if no options were specified
        $drop = empty($drop) ? $this->dropOrder : $drop;

        $class     = $input->getOption('class');
        $sm        = $this->getSchemaManager();
        $isErrored = false;

        foreach ($drop as $option) {
            $method = match ($option) {
                self::DB => 'Db',
                self::COLLECTION => 'Collection',
                self::INDEX => 'Index',
                self::SEARCH_INDEX => 'SearchIndex',
            };

            if ($option === self::SEARCH_INDEX && $input->getOption('skip-search-indexes')) {
                continue;
            }

            try {
                if (is_string($class)) {
                    $this->{'processDocument' . $method}($sm, $class, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                } else {
                    $this->{'process' . $method}($sm, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input));
                }

                $output->writeln(sprintf(
                    'Dropped <comment>%s</comment> for <info>%s</info>',
                    self::INFLECTIONS[$option][isset($class) ? 0 : 1],
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

    protected function processDocumentSearchIndex(SchemaManager $sm, string $document): void
    {
        $sm->deleteDocumentSearchIndexes($document);
    }

    protected function processSearchIndex(SchemaManager $sm): void
    {
        $sm->deleteSearchIndexes();
    }
}
