<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\Driver\PHPDriver,
    Doctrine\Common\Cache\Cache;

class Configuration
{
    private $_attributes = array();

    public function __construct()
    {
        $this->_attributes['metadataDriverImpl'] = new PHPDriver();
    }

    public function setMetadataDriverImpl(Driver $driverImpl)
    {
        $this->_attributes['metadataDriverImpl'] = $driverImpl;
    }

    public function getMetadataDriverImpl()
    {
        return isset($this->_attributes['metadataDriverImpl']) ?
            $this->_attributes['metadataDriverImpl'] : null;
    }

    public function getMetadataCacheImpl()
    {
        return isset($this->_attributes['metadataCacheImpl']) ?
                $this->_attributes['metadataCacheImpl'] : null;
    }

    public function setMetadataCacheImpl(Cache $cacheImpl)
    {
        $this->_attributes['metadataCacheImpl'] = $cacheImpl;
    }
}