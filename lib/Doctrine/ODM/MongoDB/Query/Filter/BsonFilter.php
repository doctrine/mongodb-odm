<?php

namespace Doctrine\ODM\MongoDB\Query\Filter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * The base class that user defined filters should extend.
 *
 * Handles the setting and escaping of parameters.
 */
abstract class BsonFilter
{
    /**
     * The document manager.
     * @var DocumentManager
     */
    protected $dm;

    /**
     * Parameters for the filter.
     * @var array
     */
    protected $parameters = array();

    /**
     * Constructs the BsonFilter object.
     *
     * @param DocumentManager $dm The Document Manager
     */
    final public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Sets a parameter that can be used by the filter.
     *
     * @param string $name Name of the parameter.
     * @param mixed $value Value of the parameter.
     *
     * @return BsonFilter The current Bson filter.
     */
    final public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Gets a parameter to use in a query.
     *
     * These are not like SQL parameters. These parameters can hold anything,
     * even objects. They are not automatically injected into a query, they
     * are to be used in the addFilterCriteria method.
     *
     * @param string $name Name of the parameter.
     *
     * @return mixed The parameter.
     */
    final public function getParameter($name)
    {
        if ( ! array_key_exists($name, $this->parameters)) {
            throw new \InvalidArgumentException("Filter parameter '" . $name . "' is not set.");
        }
        return $this->parameters[$name];
    }

    /**
     * Gets the criteria array to add to a query.
     *
     * If there is no criteria for the class, an empty array should be returned.
     *
     * @param ClassMetadata $class
     * @return array
     */
    abstract public function addFilterCriteria(ClassMetadata $class);
}
