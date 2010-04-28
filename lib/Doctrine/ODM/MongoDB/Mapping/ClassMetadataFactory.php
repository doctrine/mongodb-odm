<?php

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\ODM\MongoDB\EntityManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class ClassMetadataFactory
{
    private $_em;
    private $_loadedMetadata;

    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
    }

    public function getMetadataFor($className)
    {
        if ( ! isset($this->_loadedMetadata[$className])) {
            $metadata = new ClassMetadata($className);
            if (method_exists($className, 'loadMetadata')) {
                call_user_func_array(array($className, 'loadMetadata'), array($metadata));
            }
            $this->_loadedMetadata[$className] = $metadata;
        }
        return $this->_loadedMetadata[$className];
    }
}