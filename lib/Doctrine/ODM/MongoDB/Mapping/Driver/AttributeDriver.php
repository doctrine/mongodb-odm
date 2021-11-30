<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 */
class AttributeDriver extends AnnotationDriver
{
    /**
     * @param string|string[]|null $paths
     */
    public function __construct($paths = null, ?Reader $reader = null)
    {
        parent::__construct($reader ?? new AttributeReader(), $paths);
    }

    /**
     * Factory method for the Attribute Driver
     *
     * @param string[]|string $paths
     *
     * @return AttributeDriver
     */
    public static function create($paths = [], ?Reader $reader = null): AnnotationDriver
    {
        return new self($paths, $reader);
    }
}
