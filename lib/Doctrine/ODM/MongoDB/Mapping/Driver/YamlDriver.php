<?php

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class YamlDriver extends AbstractFileDriver
{
    protected $_fileExtension = '.dcm.yml';

    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $element = $this->getElement($className);
        if ( ! $element) {
            return;
        }

        if (isset($element['db'])) {
            $class->setDB($element['db']);
        }
        if (isset($element['collection'])) {
            $class->setCollection($element['collection']);
        }
        if (isset($element['fields'])) {
            foreach ($element['fields'] as $fieldName => $mapping) {
                if ( ! isset($mapping['fieldName'])) {
                    $mapping['fieldName'] = $fieldName;
                }
                $class->mapField($mapping);
            }
        }
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Components\Yaml\Yaml::load($file);
    }
}