<?php

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\Common\Cache\Cache;

class ClassMetadataFactory
{
    private $_em;
    private $_loadedMetadata;
    private $_driver;
    private $_cacheDriver;

    public function __construct(DocumentManager $em)
    {
        $this->_dm = $em;
        $this->_driver = $em->getConfiguration()->getMetadataDriverImpl();
    }

    public function setCacheDriver(Cache $cacheDriver)
    {
        $this->_cacheDriver = $cacheDriver;
    }

    public function getMetadataFor($className)
    {
        if ( ! isset($this->_loadedMetadata[$className])) {
            
            if ($this->_cacheDriver) {
                if (($cached = $this->_cacheDriver->fetch("$className\$CLASSMETADATA")) !== false) {
                    $this->_loadedMetadata[$className] = $cached;
                } else {
                    $this->_loadMetadata($className);
                    $this->_cacheDriver->save(
                        "$className\$CLASSMETADATA",
                        $this->_loadedMetadata[$className],
                        null
                    );
                }
            } else {
                $this->_loadMetadata($className);
            }
        }
        return $this->_loadedMetadata[$className];
    }

    private function _loadMetadata($className)
    {
        $class = new ClassMetadata($className);
        $this->_driver->loadMetadataForClass($className, $class);
        $this->_loadedMetadata[$className] = $class;
    }
}