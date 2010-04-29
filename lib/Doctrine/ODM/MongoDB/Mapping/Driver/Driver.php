<?php

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

interface Driver
{
    public function loadMetadataForClass($className, ClassMetadata $class);
}