<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Stubs\AttributeDriverFactory;

class AttributeDriverTest extends AbstractAnnotationDriverTestCase
{
    protected static function loadDriver(array $paths = []): MappingDriver
    {
        return AttributeDriverFactory::createAttributeDriver($paths);
    }
}
