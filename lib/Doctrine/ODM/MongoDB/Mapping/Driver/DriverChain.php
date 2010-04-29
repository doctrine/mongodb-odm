<?php

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class DriverChain implements Driver
{
    private $_drivers = array();

    public function addDriver(Driver $nestedDriver, $namespace)
    {
        $this->_drivers[$namespace] = $nestedDriver;
    }

    public function getDrivers()
    {
        return $this->_drivers;
    }

    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        foreach ($this->_drivers as $namespace => $driver) {
            if (strpos($className, $namespace) === 0) {
                $driver->loadMetadataForClass($className, $metadata);
                return;
            }
        }
    }
}