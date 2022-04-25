<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query\Filter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use InvalidArgumentException;

use function array_key_exists;

/**
 * The base class that user defined filters should extend.
 *
 * Handles the setting and escaping of parameters.
 */
abstract class BsonFilter
{
    /** @var DocumentManager */
    protected $dm;

    /**
     * Parameters for the filter.
     *
     * @var array<string, mixed>
     */
    protected $parameters = [];

    final public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Sets a parameter that can be used by the filter.
     *
     * @param mixed $value Value of the parameter.
     */
    final public function setParameter(string $name, $value): self
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
     * @return mixed The parameter.
     */
    final public function getParameter(string $name)
    {
        if (! array_key_exists($name, $this->parameters)) {
            throw new InvalidArgumentException("Filter parameter '" . $name . "' is not set.");
        }

        return $this->parameters[$name];
    }

    /**
     * Gets the criteria array to add to a query.
     *
     * If there is no criteria for the class, an empty array should be returned.
     *
     * @param ClassMetadata<object> $class Target document metadata.
     *
     * @return array<string, mixed>
     */
    abstract public function addFilterCriteria(ClassMetadata $class): array;
}
