<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\FileClassLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

use function class_exists;

class AnnotationDriverTest extends AbstractAnnotationDriverTestCase
{
    protected static function loadDriver(array $paths = []): MappingDriver
    {
        if (class_exists(FileClassLocator::class)) {
            $paths = FileClassLocator::createFromDirectories($paths);
        }

        return AnnotationDriver::create($paths);
    }
}
