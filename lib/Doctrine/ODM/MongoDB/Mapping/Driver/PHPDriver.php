<?php

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class PHPDriver implements Driver
{
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        if (method_exists($className, 'loadMetadata')) {
            call_user_func_array(array($className, 'loadMetadata'), array($class));
        }
    }
}