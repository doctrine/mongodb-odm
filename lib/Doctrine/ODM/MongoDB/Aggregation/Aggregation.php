<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Iterator as SPLIterator;
use IteratorAggregate;
use MongoDB\Collection;
use MongoDB\Driver\CursorInterface;

use function array_merge;
use function assert;

/** @phpstan-import-type PipelineExpression from Builder */
final class Aggregation implements IteratorAggregate
{
    /**
     * @param array<string, mixed> $pipeline
     * @param array<string, mixed> $options
     * @phpstan-param PipelineExpression $pipeline
     */
    public function __construct(private DocumentManager $dm, private ?ClassMetadata $classMetadata, private Collection $collection, private array $pipeline, private array $options = [], private bool $rewindable = true)
    {
    }

    public function getIterator(): Iterator
    {
        // Force cursor to be used
        $options = array_merge($this->options, ['cursor' => true]);

        $cursor = $this->collection->aggregate($this->pipeline, $options);
        // This assertion can be dropped when requiring mongodb/mongodb 1.17.0
        assert($cursor instanceof CursorInterface);
        assert($cursor instanceof SPLIterator);

        return $this->prepareIterator($cursor);
    }

    private function prepareIterator(CursorInterface&SPLIterator $cursor): Iterator
    {
        if ($this->classMetadata) {
            $cursor = new HydratingIterator($cursor, $this->dm->getUnitOfWork(), $this->classMetadata);
        }

        return $this->rewindable ? new CachingIterator($cursor) : new UnrewindableIterator($cursor);
    }
}
