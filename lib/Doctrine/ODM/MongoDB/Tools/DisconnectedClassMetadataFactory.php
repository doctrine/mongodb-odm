<?php

namespace Doctrine\ODM\MongoDB\Tools;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * The DisconnectedClassMetadataFactory is used to create ClassMetadata objects
 * that do not require the document class actually exist. This allows us to
 * load some mapping information and use it to do things like generate code
 * from the mapping information.
 *
 * @since   1.0
 */
class DisconnectedClassMetadataFactory extends ClassMetadataFactory
{
    /**
     * @override
     */
    protected function newClassMetadataInstance($className)
    {
        $metadata = new ClassMetadataInfo($className);
        if (strpos($className, "\\") !== false) {
            $metadata->namespace = strrev(substr( strrev($className), strpos(strrev($className), "\\")+1 ));
        } else {
            $metadata->namespace = '';
        }
        return $metadata;
    }

    /**
     * @override
     */
    protected function getParentClasses($name)
    {
        return array();
    }

    /**
     * @override
     */
    protected function validateIdentifier($class)
    {
        // do nothing as the DisconnectedClassMetadataFactory cannot validate an inherited id
    }
}
