<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use InvalidArgumentException;
use const E_USER_DEPRECATED;
use function array_map;
use function array_values;
use function sprintf;
use function trigger_error;

/**
 * Collection class for all the query filters.
 *
 * @final
 */
class FilterCollection
{
    /**
     * The used Configuration.
     *
     * @var Configuration
     */
    private $config;

    /**
     * The DocumentManager that "owns" this FilterCollection instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * Instances of enabled filters.
     *
     * @var BsonFilter[]
     */
    private $enabledFilters = [];

    /**
     * The CriteriaMerger instance.
     *
     * @var CriteriaMerger
     */
    private $cm;

    public function __construct(DocumentManager $dm, ?CriteriaMerger $cm = null)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        $this->dm = $dm;
        $this->cm = $cm ?: new CriteriaMerger();

        $this->config = $dm->getConfiguration();
    }

    /**
     * Get all the enabled filters.
     *
     * @return BsonFilter[]
     */
    public function getEnabledFilters() : array
    {
        return $this->enabledFilters;
    }

    /**
     * Enables a filter from the collection.
     *
     * @throws InvalidArgumentException If the filter does not exist.
     */
    public function enable(string $name) : BsonFilter
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException("Filter '" . $name . "' does not exist.");
        }

        if (! $this->isEnabled($name)) {
            $filterClass      = $this->config->getFilterClassName($name);
            $filterParameters = $this->config->getFilterParameters($name);
            $filter           = new $filterClass($this->dm);

            foreach ($filterParameters as $param => $value) {
                $filter->setParameter($param, $value);
            }

            $this->enabledFilters[$name] = $filter;
        }

        return $this->enabledFilters[$name];
    }

    /**
     * Disables a filter.
     *
     * @throws InvalidArgumentException If the filter does not exist.
     */
    public function disable(string $name) : BsonFilter
    {
        // Get the filter to return it
        $filter = $this->getFilter($name);

        unset($this->enabledFilters[$name]);

        return $filter;
    }

    /**
     * Get an enabled filter from the collection.
     *
     * @throws InvalidArgumentException If the filter is not enabled.
     */
    public function getFilter(string $name) : BsonFilter
    {
        if (! $this->isEnabled($name)) {
            throw new InvalidArgumentException("Filter '" . $name . "' is not enabled.");
        }
        return $this->enabledFilters[$name];
    }

    /**
     * Checks whether filter with given name is defined.
     *
     * @param string $name Name of the filter.
     *
     * @return bool true if the filter exists, false if not.
     */
    public function has(string $name) : bool
    {
        return $this->config->getFilterClassName($name) !== null;
    }

    /**
     * Checks whether filter with given name is enabled.
     */
    public function isEnabled(string $name) : bool
    {
        return isset($this->enabledFilters[$name]);
    }

    /**
     * Gets enabled filter criteria.
     */
    public function getFilterCriteria(ClassMetadata $class) : array
    {
        if (empty($this->enabledFilters)) {
            return [];
        }

        return $this->cm->merge(
            ...array_map(
                static function ($filter) use ($class) {
                    return $filter->addFilterCriteria($class);
                },
                array_values($this->enabledFilters)
            )
        );
    }
}
