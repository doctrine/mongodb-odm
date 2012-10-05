<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;

class MetadataDriverMock implements MappingDriver
{
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        return;
    }

    public function isTransient($className)
    {
        return false;
    }

    public function getAllClassNames()
    {
        return array();
    }
}