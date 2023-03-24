<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class AttributeDriverTest extends AbstractAnnotationDriverTest
{
    protected function loadDriver(): MappingDriver
    {
        return new AttributeDriver();
    }
}
