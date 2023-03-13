<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/** @requires PHP >= 8.0 */
class AttributeDriverTest extends AbstractAnnotationDriverTestCase
{
    protected static function loadDriver(): MappingDriver
    {
        return new AttributeDriver();
    }
}
