<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console;

use ArrayIterator;
use Countable;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use FilterIterator;

use function count;
use function iterator_count;
use function iterator_to_array;
use function strpos;

/**
 * Used by CLI Tools to restrict entity-based commands to given patterns.
 */
class MetadataFilter extends FilterIterator implements Countable
{
    /**
     * Filter Metadatas by one or more filter options.
     *
     * @param  ClassMetadata<object>[] $metadatas
     * @param  string[]|string         $filter
     *
     * @return ClassMetadata<object>[]
     */
    public static function filter(array $metadatas, $filter): array
    {
        $metadatas = new MetadataFilter(new ArrayIterator($metadatas), $filter);

        return iterator_to_array($metadatas);
    }

    /** @var string[] */
    private array $_filter = [];

    /**
     * @param string[]|string                                 $filter
     * @param ArrayIterator<array-key, ClassMetadata<object>> $metadata
     */
    public function __construct(ArrayIterator $metadata, $filter)
    {
        $this->_filter = (array) $filter;

        parent::__construct($metadata);
    }

    public function accept(): bool
    {
        if (count($this->_filter) === 0) {
            return true;
        }

        $it       = $this->getInnerIterator();
        $metadata = $it->current();

        foreach ($this->_filter as $filter) {
            if (strpos($metadata->name, $filter) !== false) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return iterator_count($this->getInnerIterator());
    }
}
