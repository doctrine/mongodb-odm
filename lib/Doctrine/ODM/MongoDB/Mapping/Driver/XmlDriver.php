<?php

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class XmlDriver extends AbstractFileDriver
{
    protected $_fileExtension = '.dcm.xml';

    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $xmlRoot = $this->getElement($className);
        if ( ! $xmlRoot) {
            return;
        }

        if (isset($xmlRoot['db'])) {
            $class->setDB((string) $xmlRoot['db']);
        }
        if (isset($xmlRoot['collection'])) {
            $class->setCollection((string) $xmlRoot['collection']);
        }
        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $field) {
                $mapping = array();
                $attributes = $field->attributes();
                foreach ($attributes as $key => $value) {
                    $mapping[$key] = (string) $value;
                }
                $class->mapField($mapping);
            }
        }
    }

    protected function _loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->document)) {
            foreach ($xmlElement->document as $documentElement) {
                $documentName = (string)$documentElement['name'];
                $result[$documentName] = $documentElement;
            }
        }

        return $result;
    }
}