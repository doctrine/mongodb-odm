<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\SchemaManager;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_filter;
use function is_string;
use function sprintf;

#[AsCommand('odm:schema:create')]
class CreateCommand extends AbstractCommand
{
    /** @var string[] */
    private array $createOrder = [self::COLLECTION, self::INDEX, self::SEARCH_INDEX];

    /* @var array<string, list<string>> */
    private const INFLECTIONS = [
        self::COLLECTION => ['collection', 'collections'],
        self::INDEX => ['index(es)', 'indexes'],
        self::SEARCH_INDEX => ['search index(es)', 'search indexes'],
    ];

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('odm:schema:create')
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Document class to process (default: all classes)')
            ->addOption(self::COLLECTION, null, InputOption::VALUE_NONE, 'Create collections')
            ->addOption(self::INDEX, null, InputOption::VALUE_NONE, 'Create indexes')
            ->addOption(self::SEARCH_INDEX, null, InputOption::VALUE_NONE, 'Create search indexes')
            ->addOption('skip-search-indexes', null, InputOption::VALUE_NONE, 'Skip processing of search indexes')
            ->addOption('background', null, InputOption::VALUE_NONE, sprintf('Create indexes in background (requires "%s" option)', self::INDEX))
            ->setDescription('Create databases, collections and indexes for your documents');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $create = array_filter($this->createOrder, static fn (string $option): bool => (bool) $input->getOption($option));

        // Default to the full creation order if no options were specified
        $create = empty($create) ? $this->createOrder : $create;

        $class      = $input->getOption('class');
        $background = (bool) $input->getOption('background');

        $sm        = $this->getSchemaManager();
        $isErrored = false;

        foreach ($create as $option) {
            $method = match ($option) {
                self::COLLECTION => 'Collection',
                self::INDEX => 'Index',
                self::SEARCH_INDEX => 'SearchIndex',
            };

            if ($option === self::SEARCH_INDEX && $input->getOption('skip-search-indexes')) {
                continue;
            }

            try {
                if (isset($class)) {
                    // @phpstan-ignore arguments.count, arguments.count
                    $this->{'processDocument' . $method}($sm, $class, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input), $background);
                } else {
                    // @phpstan-ignore arguments.count, arguments.count
                    $this->{'process' . $method}($sm, $this->getMaxTimeMsFromInput($input), $this->getWriteConcernFromInput($input), $background);
                }

                $output->writeln(sprintf(
                    'Created <comment>%s</comment> for <info>%s</info>',
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

    protected function processDocumentCollection(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $sm->createDocumentCollection($document, $maxTimeMs, $writeConcern);
    }

    protected function processCollection(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern): void
    {
        $sm->createCollections($maxTimeMs, $writeConcern);
    }

    protected function processDocumentIndex(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false): void
    {
        $sm->ensureDocumentIndexes($document, $maxTimeMs, $writeConcern, $background);
    }

    protected function processIndex(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern, bool $background = false): void
    {
        $sm->ensureIndexes($maxTimeMs, $writeConcern, $background);
    }

    protected function processDocumentSearchIndex(SchemaManager $sm, string $document): void
    {
        $sm->createDocumentSearchIndexes($document);
    }

    protected function processSearchIndex(SchemaManager $sm): void
    {
        $sm->createSearchIndexes();
    }
}
