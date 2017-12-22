<?php

namespace Doctrine\ODM\MongoDB\Tools\Console;

/**
 * Used by CLI Tools to restrict entity-based commands to given patterns.
 *
 * @since       1.0
 */
class MetadataFilter extends \FilterIterator implements \Countable
{
    /**
     * Filter Metadatas by one or more filter options.
     *
     * @param array $metadatas
     * @param array|string $filter
     * @return array
     */
    public static function filter(array $metadatas, $filter)
    {
        $metadatas = new MetadataFilter(new \ArrayIterator($metadatas), $filter);
        return iterator_to_array($metadatas);
    }

    /**
     * @var array
     */
    private $_filter = array();

    /**
     * @param \ArrayIterator $metadata
     * @param array|string $filter
     */
    public function __construct(\ArrayIterator $metadata, $filter)
    {
        $this->_filter = (array) $filter;
        parent::__construct($metadata);
    }

    /**
     * @return bool
     */
    public function accept()
    {
        if (count($this->_filter) == 0) {
            return true;
        }

        $it = $this->getInnerIterator();
        $metadata = $it->current();

        foreach ($this->_filter AS $filter) {
            if (strpos($metadata->name, $filter) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->getInnerIterator());
    }
}
