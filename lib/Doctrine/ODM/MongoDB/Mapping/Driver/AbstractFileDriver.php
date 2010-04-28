<?php

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\MongoDBException;

abstract class AbstractFileDriver implements Driver
{
    protected $_paths = array();
    protected $_fileExtension;

    public function __construct($paths)
    { 
        $this->addPaths((array) $paths);
    }

    public function addPaths(array $paths)
    {
        $this->_paths = array_unique(array_merge($this->_paths, $paths));
    }

    public function getPaths()
    {
        return $this->_paths;
    }

    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }

    public function getFileExtension()
    {
        return $this->_fileExtension;
    }

    public function getElement($className)
    {
        if ($file = $this->_findMappingFile($className)) {
            $result = $this->_loadMappingFile($file);
            return $result[$className];
        } else {
            return false;
        }
    }

    protected function _findMappingFile($className)
    {
        $fileName = str_replace('\\', '.', $className) . $this->_fileExtension;
        
        // Check whether file exists
        foreach ((array) $this->_paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $fileName)) {
                return $path . DIRECTORY_SEPARATOR . $fileName;
            }
        }

        return false;
    }

    abstract protected function _loadMappingFile($file);
}