<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 */
class AnnotationDriver extends AttributeDriver
{
    /**
     * The annotation reader.
     *
     * @internal this property will be private in 3.0
     *
     * @var Reader
     */
    protected $reader;

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     *
     * @param Reader               $reader The AnnotationReader to use, duck-typed.
     * @param string|string[]|null $paths  One or multiple paths where mapping classes can be found.
     */
    public function __construct($reader, $paths = null)
    {
        $this->reader = $reader;

        $this->addPaths((array) $paths);
    }

    /**
     * Factory method for the Annotation Driver
     *
     * @param string[]|string $paths
     */
    public static function create($paths = [], ?Reader $reader = null): AnnotationDriver
    {
        return new self($reader ?? new AnnotationReader(), $paths);
    }
}
