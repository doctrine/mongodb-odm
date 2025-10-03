<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Doctrine\ODM\MongoDB\Iterator\IterableResult;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\SchemaException;
use MongoDB\Collection;
use MongoDB\Driver\CursorInterface;

use function array_merge;
use function current;
use function in_array;
use function key;

/** @phpstan-import-type PipelineExpression from Builder */
final class Aggregation implements IterableResult
{
    /**
     * @param array<string, mixed> $pipeline
     * @param array<string, mixed> $options
     * @phpstan-param PipelineExpression $pipeline
     */
    public function __construct(private DocumentManager $dm, private ?ClassMetadata $classMetadata, private Collection $collection, private array $pipeline, private array $options = [], private bool $rewindable = true)
    {
    }

    public function execute(): Iterator
    {
        return $this->getIterator();
    }

    /**
     * Execute the query and return the first result.
     *
     * @return array<string, mixed>|object|null
     */
    public function getSingleResult(): mixed
    {
        $clone = clone $this;

        // Limit the pipeline to a single result for efficiency
        $this->pipeline[] = ['$limit' => 1];

        return $clone->getIterator()->current() ?: null;
    }

    public function getIterator(): Iterator
    {
        // Force cursor to be used
        $options = array_merge($this->options, ['cursor' => true]);

        $cursor = $this->collection->aggregate($this->pipeline, $options);

        return $this->prepareIterator($cursor);
    }

    private function prepareIterator(CursorInterface $cursor): Iterator
    {
        if ($this->classMetadata) {
            $cursor = new HydratingIterator($cursor, $this->dm->getUnitOfWork(), $this->classMetadata);
        }

        $iterator = $this->rewindable ? new CachingIterator($cursor) : new UnrewindableIterator($cursor);

        $this->assertSearchIndexExistsForEmptyResult($iterator);

        return $iterator;
    }

    /**
     * If the server implements a server-side error for missing search indexes,
     * this assertion can be removed.
     *
     * @see https://jira.mongodb.org/browse/SERVER-110974
     * @see Configuration::setAssertSearchIndexExistsForEmptyResult()
     *
     * @param CachingIterator<mixed>|UnrewindableIterator<mixed> $iterator
     */
    private function assertSearchIndexExistsForEmptyResult(CachingIterator|UnrewindableIterator $iterator): void
    {
        // The iterator is always rewinded
        if ($iterator->key() !== null) {
            return; // Results not empty
        }

        if (! $this->dm->getConfiguration()->assertSearchIndexExistsForEmptyResult()) {
            return; // Feature disabled
        }

        // Search stages must be the first stage in the pipeline
        $stage = $this->pipeline[0] ?? null;
        if (! $stage || ! in_array(key($stage), ['$search', '$searchMeta', '$vectorSearch'], true)) {
            return; // Not a search aggregation
        }

        // @phpcs:ignore SlevomatCodingStandard.PHP.UselessParentheses
        $indexName = ((object) current($stage))->index ?? 'default';
        if ($this->collection->listSearchIndexes(['filter' => ['name' => $indexName]])->key() !== null) {
            return; // Index exists
        }

        throw SchemaException::searchIndexNotFound($this->collection->getNamespace(), $indexName);
    }
}
