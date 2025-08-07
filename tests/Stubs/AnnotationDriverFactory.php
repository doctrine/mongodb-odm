<?php

declare(strict_types=1);

namespace Stubs;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\DirectoryFilesIterator;
use Doctrine\Persistence\Mapping\Driver\FilePathNameIterator;

final class AnnotationDriverFactory
{
    /** @param list<string> $paths */
    public static function createAnnotationDriver(array $paths = [], ?Reader $reader = null): AnnotationDriver
    {
        if (! AttributeDriverFactory::isFilePathsSupported()) {
            return AnnotationDriver::create($paths, $reader);
        }

        $filePaths = new FilePathNameIterator(new DirectoryFilesIterator($paths));

        return AnnotationDriver::create($filePaths, $reader);
    }
}
