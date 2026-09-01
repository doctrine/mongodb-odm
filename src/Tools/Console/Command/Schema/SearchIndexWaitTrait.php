<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\SchemaManager;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function is_numeric;
use function sprintf;
use function strtotime;

/** @internal */
trait SearchIndexWaitTrait
{
    /** @return ClassMetadataFactoryInterface */
    abstract protected function getMetadataFactory();

    /**
     * Returns the search index wait time in milliseconds, or null when --wait was not passed.
     *
     * Accepts an integer number of milliseconds or a duration string parsable by
     * strtotime() such as "30 seconds", "1minute" or "1 hour". When --wait is
     * passed without a value, a default timeout of 5 minutes is used.
     */
    private function getWaitTimeMsFromInput(InputInterface $input): ?int
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
     * @return list<class-string>
     */
    private function getClassNamesWithSearchIndexes(?string $documentName): array
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
     */
    private function waitForSearchIndexes(SchemaManager $sm, OutputInterface $output, ?string $documentName, ?int $waitTimeMs): void
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
}
