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
     * Factory method for the Attribute Driver
     *
     * @param string[]|string $paths
     */
    public static function create($paths = [], ?Reader $reader = null): AnnotationDriver
    {
        if ($reader === null) {
            $reader = new AttributeReader();
        }

        return new self($reader, $paths);
    }
}
