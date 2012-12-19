<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;

class YamlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('This test requires the Symfony YAML component');
        }

        return new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');
    }
}