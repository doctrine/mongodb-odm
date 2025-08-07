<?php

declare(strict_types=1);

namespace Stubs;

use Composer\InstalledVersions;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\DirectoryFilesIterator;
use Doctrine\Persistence\Mapping\Driver\FilePathNameIterator;

use function version_compare;

final class AttributeDriverFactory
{
    /** @param list<string> $paths */
    public static function createAttributeDriver(array $paths = [], ?Reader $reader = null): AttributeDriver
    {
        if (! self::isFilePathsSupported()) {
            return AttributeDriver::create($paths, $reader);
        }

        $filePaths = new FilePathNameIterator(new DirectoryFilesIterator($paths));

        return AttributeDriver::create($filePaths, $reader);
    }

    public static function isFilePathsSupported(): bool
    {
        return version_compare(InstalledVersions::getVersion('doctrine/persistence'), '4.1', '>=');
    }
}
