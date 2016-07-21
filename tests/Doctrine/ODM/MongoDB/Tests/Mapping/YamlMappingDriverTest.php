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
        $className = __NAMESPACE__.'\AbstractMappingDriverAlternateUser';
        $mappingDriver = new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);

        foreach (array('address', 'phonenumbers') as $referencedField) {
            foreach (array('inversedBy', 'limit', 'mappedBy', 'repositoryMethod', 'storeAs', 'skip', 'strategy', 'targetDocument') as $key) {
                $this->assertArrayHasKey($key, $class->fieldMappings[$referencedField]);
            }
        }

        foreach (array('embeddedPhonenumber', 'otherPhonenumbers') as $embeddedField) {
            foreach (array('strategy', 'targetDocument', 'collectionClass') as $key) {
                $this->assertArrayHasKey($key, $class->fieldMappings[$embeddedField]);
            }
        }
    }

    public function testGetAssociationCollectionClass()
    {
        $className = __NAMESPACE__.'\AbstractMappingDriverUser';
        $mappingDriver = new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);
        $this->assertEquals('Doctrine\ODM\MongoDB\Tests\Mapping\PhonenumberCollection', $class->getAssociationCollectionClass('phonenumbers'));
        $this->assertEquals('Doctrine\ODM\MongoDB\Tests\Mapping\PhonenumberCollection', $class->getAssociationCollectionClass('otherPhonenumbers'));
    }

    public function testFieldLevelIndexSyntaxWithBooleanValues()
    {
        $className = __NAMESPACE__.'\AbstractMappingDriverAlternateUser';
        $mappingDriver = new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);

        $this->assertEquals(1, $class->indexes[0]['keys']['username']);
        $this->assertTrue($class->indexes[0]['options']['unique']);
        $this->assertFalse(isset($class->indexes[0]['options']['sparse']));

        $this->assertEquals(1, $class->indexes[1]['keys']['firstName']);
        $this->assertFalse(isset($class->indexes[1]['options']['unique']));
        $this->assertFalse(isset($class->indexes[1]['options']['sparse']));

        $this->assertEquals(1, $class->indexes[2]['keys']['middleName']);
        $this->assertFalse(isset($class->indexes[2]['options']['unique']));
        $this->assertTrue($class->indexes[2]['options']['sparse']);
    }
}

class AbstractMappingDriverAlternateUser
{
    public $id;
    public $username;
    public $firstName;
    public $middleName;
    public $address;
    public $phonenumbers;
    public $embeddedPhonenumber;
    public $otherPhonenumbers;
}

class PhonenumberCollection extends \Doctrine\Common\Collections\ArrayCollection
{

}
