<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class AnnotationDriverTest extends AbstractAnnotationDriverTestCase
{
    protected static function loadDriver(): MappingDriver
    {
        $reader = new AnnotationReader();

        return new AnnotationDriver($reader);
    }
}
