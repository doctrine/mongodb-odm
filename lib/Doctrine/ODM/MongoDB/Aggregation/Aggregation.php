<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use IteratorAggregate;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;

use function array_merge;
use function assert;

/** @psalm-import-type PipelineExpression from Builder */
final class Aggregation implements IteratorAggregate
{
    /**
     * @param array<string, mixed> $pipeline
     * @param array<string, mixed> $options
     * @psalm-param PipelineExpression $pipeline
     */
    public function __construct(private DocumentManager $dm, private ?ClassMetadata $classMetadata, private Collection $collection, private array $pipeline, private array $options = [], private bool $rewindable = true)
    {
    }

    public function getIterator(): Iterator
    {
        // Force cursor to be used
        $options = array_merge($this->options, ['cursor' => true]);

        $cursor = $this->collection->aggregate($this->pipeline, $options);
        assert($cursor instanceof Cursor);

        return $this->prepareIterator($cursor);
    }

    private function prepareIterator(Cursor $cursor): Iterator
    {
        if ($this->classMetadata) {
            $cursor = new HydratingIterator($cursor, $this->dm->getUnitOfWork(), $this->classMetadata);
        }

        return $this->rewindable ? new CachingIterator($cursor) : new UnrewindableIterator($cursor);
    }
}
