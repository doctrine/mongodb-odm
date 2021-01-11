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

final class Aggregation implements IteratorAggregate
{
    /** @var DocumentManager */
    private $dm;

    /** @var ClassMetadata|null */
    private $classMetadata;

    /** @var Collection */
    private $collection;

    /** @var array */
    private $pipeline;

    /** @var array */
    private $options;

    /** @var bool */
    private $rewindable;

    public function __construct(DocumentManager $dm, ?ClassMetadata $classMetadata, Collection $collection, array $pipeline, array $options = [], bool $rewindable = true)
    {
        $this->dm            = $dm;
        $this->classMetadata = $classMetadata;
        $this->collection    = $collection;
        $this->pipeline      = $pipeline;
        $this->options       = $options;
        $this->rewindable    = $rewindable;
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
