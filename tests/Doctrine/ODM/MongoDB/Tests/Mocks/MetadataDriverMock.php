<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\Common\Persistence\Mapping\Driver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;

class MetadataDriverMock implements Driver
{
    public function loadMetadataForClass($className, ClassMetadataInterface $metadata)
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