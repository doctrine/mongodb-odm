<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\Driver\Driver,
    Doctrine\ODM\MongoDB\Mapping\Driver\PHPDriver,
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

    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     *
     * @param string $dir
     */
    public function setProxyDir($dir)
    {
        $this->_attributes['proxyDir'] = $dir;
    }

    /**
     * Gets the directory where Doctrine generates any necessary proxy class files.
     *
     * @return string
     */
    public function getProxyDir()
    {
        return isset($this->_attributes['proxyDir']) ?
                $this->_attributes['proxyDir'] : null;
    }

    /**
     * Gets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     *
     * @return boolean
     */
    public function getAutoGenerateProxyClasses()
    {
        return isset($this->_attributes['autoGenerateProxyClasses']) ?
                $this->_attributes['autoGenerateProxyClasses'] : true;
    }

    /**
     * Sets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     *
     * @param boolean $bool
     */
    public function setAutoGenerateProxyClasses($bool)
    {
        $this->_attributes['autoGenerateProxyClasses'] = $bool;
    }

    /**
     * Gets the namespace where proxy classes reside.
     * 
     * @return string
     */
    public function getProxyNamespace()
    {
        return isset($this->_attributes['proxyNamespace']) ?
                $this->_attributes['proxyNamespace'] : null;
    }

    /**
     * Sets the namespace where proxy classes reside.
     * 
     * @param string $ns
     */
    public function setProxyNamespace($ns)
    {
        $this->_attributes['proxyNamespace'] = $ns;
    }
}