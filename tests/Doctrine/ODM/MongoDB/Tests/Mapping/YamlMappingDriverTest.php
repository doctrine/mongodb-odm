<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
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

    public function testAlternateRelationshipMappingSyntaxShouldSetDefaults()
    {
        $className = __NAMESPACE__.'\AlternateUser';
        $mappingDriver = new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);

        foreach (array('address', 'phonenumbers') as $referencedField) {
            foreach (array('inversedBy', 'limit', 'mappedBy', 'repositoryMethod', 'simple', 'skip', 'strategy', 'targetDocument') as $key) {
                $this->assertArrayHasKey($key, $class->fieldMappings[$referencedField]);
            }
        }

        foreach (array('embeddedPhonenumber', 'otherPhonenumbers') as $embeddedField) {
            foreach (array('strategy', 'targetDocument') as $key) {
                $this->assertArrayHasKey($key, $class->fieldMappings[$embeddedField]);
            }
        }
    }
}

class AlternateUser
{
    public $id;
    public $address;
    public $phonenumbers;
    public $embeddedPhonenumber;
    public $otherPhonenumbers;
}
