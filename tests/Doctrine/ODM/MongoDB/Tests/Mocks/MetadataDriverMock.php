<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

class MetadataDriverMock implements \Doctrine\ODM\MongoDB\Mapping\Driver\Driver
{
    public function loadMetadataForClass($className, \Doctrine\ODM\MongoDB\Mapping\ClassMetadata $metadata)
    {
        return;
    }
}