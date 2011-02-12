<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

class MetadataDriverMock implements \Doctrine\ODM\MongoDB\Mapping\Driver\Driver
{
    public function loadMetadataForClass($className, \Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo $metadata)
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