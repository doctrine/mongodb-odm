<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use function array_map;
use function call_user_func_array;

/**
 * Collection class for all the query filters.
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
     * @var array
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
        $this->dm = $dm;
        $this->cm = $cm ?: new CriteriaMerger();

        $this->config = $dm->getConfiguration();
    }

    /**
     * Get all the enabled filters.
     *
     * @return array The enabled filters.
     */
    public function getEnabledFilters()
    {
        return $this->enabledFilters;
    }

    /**
     * Enables a filter from the collection.
     *
     * @param string $name Name of the filter.
     *
     * @throws \InvalidArgumentException If the filter does not exist.
     *
     * @return BsonFilter The enabled filter.
     */
    public function enable($name)
    {
        if (! $this->has($name)) {
            throw new \InvalidArgumentException("Filter '" . $name . "' does not exist.");
        }

        if (! $this->isEnabled($name)) {
            $filterClass = $this->config->getFilterClassName($name);
            $filterParameters = $this->config->getFilterParameters($name);
            $filter = new $filterClass($this->dm);

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
     * @param string $name Name of the filter.
     *
     * @return BsonFilter The disabled filter.
     *
     * @throws \InvalidArgumentException If the filter does not exist.
     */
    public function disable($name)
    {
        // Get the filter to return it
        $filter = $this->getFilter($name);

        unset($this->enabledFilters[$name]);

        return $filter;
    }

    /**
     * Get an enabled filter from the collection.
     *
     * @param string $name Name of the filter.
     *
     * @return BsonFilter The filter.
     *
     * @throws \InvalidArgumentException If the filter is not enabled.
     */
    public function getFilter($name)
    {
        if (! $this->isEnabled($name)) {
            throw new \InvalidArgumentException("Filter '" . $name . "' is not enabled.");
        }
        return $this->enabledFilters[$name];
    }

    /**
     * Checks whether filter with given name is defined.
     *
     * @param string $name Name of the filter.
     * @return bool true if the filter exists, false if not.
     */
    public function has($name)
    {
        return $this->config->getFilterClassName($name) !== null;
    }

    /**
     * Checks whether filter with given name is enabled.
     *
     * @param string $name Name of the filter
     * @return bool
     */
    public function isEnabled($name)
    {
        return isset($this->enabledFilters[$name]);
    }

    /**
     * Gets enabled filter criteria.
     *
     * @return array
     */
    public function getFilterCriteria(ClassMetadata $class)
    {
        if (empty($this->enabledFilters)) {
            return [];
        }

        return call_user_func_array(
            [$this->cm, 'merge'],
            array_map(
                function ($filter) use ($class) {
                    return $filter->addFilterCriteria($class);
                },
                $this->enabledFilters
            )
        );
    }
}
