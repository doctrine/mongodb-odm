<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\SchemaManager;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function is_numeric;
use function is_string;
use function sprintf;
use function strtotime;

abstract class AbstractCommand extends Command
{
    use AbstractCommandCompatibility;

    public const DB           = 'db';
    public const COLLECTION   = 'collection';
    public const INDEX        = 'index';
    public const SEARCH_INDEX = 'search-index';

    private function configureCommonOptions(): void
    {
        $this
            ->addOption('maxTimeMs', null, InputOption::VALUE_REQUIRED, 'An optional maxTimeMs that will be used for all schema operations.')
            ->addOption('w', null, InputOption::VALUE_REQUIRED, 'An optional w option for the write concern that will be used for all schema operations.')
            ->addOption('wTimeout', null, InputOption::VALUE_REQUIRED, 'An optional wTimeout option for the write concern that will be used for all schema operations. Using this option without a w option will cause an exception to be thrown.')
            ->addOption('journal', null, InputOption::VALUE_REQUIRED, 'An optional journal option for the write concern that will be used for all schema operations. Using this option without a w option will cause an exception to be thrown.');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processDocumentCollection(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support collections');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processCollection(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support collections');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processDocumentDb(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support databases');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processDb(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support databases');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processDocumentIndex(SchemaManager $sm, string $document, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support indexes');
    }

    /**
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function processIndex(SchemaManager $sm, ?int $maxTimeMs, ?WriteConcern $writeConcern)
    {
        throw new BadMethodCallException('This command does not support indexes');
    }

    /** @throws BadMethodCallException */
    protected function processSearchIndex(SchemaManager $sm): void
    {
        throw new BadMethodCallException('This command does not support search indexes');
    }

    /**
     * @param class-string $document
     *
     * @throws BadMethodCallException
     */
    protected function processDocumentSearchIndex(SchemaManager $sm, string $document): void
    {
        throw new BadMethodCallException('This command does not support search indexes');
    }

    /** @return SchemaManager */
    protected function getSchemaManager()
    {
        return $this->getDocumentManager()->getSchemaManager();
    }

    /** @return DocumentManager */
    protected function getDocumentManager()
    {
        return $this->getHelper('documentManager')->getDocumentManager();
    }

    /** @return ClassMetadataFactoryInterface */
    protected function getMetadataFactory()
    {
        return $this->getDocumentManager()->getMetadataFactory();
    }

    protected function getMaxTimeMsFromInput(InputInterface $input): ?int
    {
        $maxTimeMs = $input->getOption('maxTimeMs');

        return is_string($maxTimeMs) ? (int) $maxTimeMs : null;
    }

    /**
     * Returns the search index wait time in milliseconds, or null when --wait was not passed.
     *
     * Accepts an integer number of milliseconds or a duration string parsable by
     * strtotime() such as "30 seconds", "1minute" or "1 hour". When --wait is
     * passed without a value, a default timeout of 5 minutes is used.
     *
     * @internal
     */
    protected function getWaitTimeMsFromInput(InputInterface $input): ?int
    {
        if (! $input->hasOption('wait')) {
            return null;
        }

        $value = $input->getOption('wait');

        if ($value === false) {
            // --wait was not passed
            return null;
        }

        if ($value === null || $value === '' || $value === true) {
            // --wait passed with no value: fall back to the default timeout
            return 300_000;
        }

        if (is_numeric($value)) {
            $ms = (int) $value;
            if ($ms < 1) {
                throw new InvalidOptionException('The "--wait" option must be a positive number of milliseconds.');
            }

            return $ms;
        }

        $seconds = strtotime('+' . $value, 0);
        if ($seconds === false || $seconds <= 0) {
            throw new InvalidOptionException(sprintf('Invalid duration "%s" for "--wait" option. Use formats like "30 seconds", "1 minute", "1 hour" or a positive integer of milliseconds.', $value));
        }

        return $seconds * 1000;
    }

    /**
     * Returns the list of mapped class names that define search indexes.
     *
     * When $documentName is provided, the list contains that class if it
     * defines search indexes, otherwise it is empty.
     *
     * @internal
     *
     * @return list<class-string>
     */
    protected function getClassNamesWithSearchIndexes(?string $documentName): array
    {
        $factory = $this->getMetadataFactory();

        if ($documentName !== null) {
            $class = $factory->getMetadataFor($documentName);

            return $class->hasSearchIndexes() ? [$class->getName()] : [];
        }

        $classNames = [];
        foreach ($factory->getAllMetadata() as $class) {
            if (! $class->hasSearchIndexes()) {
                continue;
            }

            $classNames[] = $class->getName();
        }

        return $classNames;
    }

    /**
     * Waits until search indexes are queryable for the given class (or all
     * classes when null). Does nothing when $waitTimeMs is null or when no
     * mapped class declares a search index.
     *
     * @internal
     */
    protected function waitForSearchIndexes(SchemaManager $sm, OutputInterface $output, ?string $documentName, ?int $waitTimeMs): void
    {
        if ($waitTimeMs === null) {
            return;
        }

        $classNames = $this->getClassNamesWithSearchIndexes($documentName);
        if (count($classNames) === 0) {
            return;
        }

        $target = $documentName ?? 'all classes';

        $output->writeln(sprintf(
            'Waiting up to <comment>%d ms</comment> for search indexes to become ready for <info>%s</info>',
            $waitTimeMs,
            $target,
        ));

        $sm->waitForSearchIndexes($classNames, $waitTimeMs);

        $output->writeln(sprintf('Search indexes are ready for <info>%s</info>', $target));
    }

    protected function getWriteConcernFromInput(InputInterface $input): ?WriteConcern
    {
        $w        = $input->getOption('w');
        $wTimeout = $input->getOption('wTimeout');
        $journal  = $input->getOption('journal');

        if (! is_string($w)) {
            if ($wTimeout !== null || $journal !== null) {
                throw new InvalidOptionException('The "wTimeout" or "journal" options can only be used when passing a "w" option.');
            }

            return null;
        }

        if (is_numeric($w)) {
            $w = (int) $w;
        }

        $wTimeout = is_string($wTimeout) ? (int) $wTimeout : 0;
        $journal  = is_string($journal) ? (bool) $journal : false;

        return new WriteConcern($w, $wTimeout, $journal);
    }
}
