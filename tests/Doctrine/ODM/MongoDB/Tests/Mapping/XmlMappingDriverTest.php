<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;

class XmlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        return new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
    }

    public function testSetShardKeyOptionsByAttributes()
    {
        $class = new ClassMetadataInfo('doc');
        $driver = $this->_loadDriver();
        $element = new \SimpleXmlElement('<shard-key unique="true" numInitialChunks="4096"><key name="_id"/></shard-key>');

        /** @uses XmlDriver::setShardKey */
        $m = new \ReflectionMethod(get_class($driver), 'setShardKey');
        $m->setAccessible(true);
        $m->invoke($driver, $class, $element);

        $this->assertTrue($class->isSharded());
        $shardKey = $class->getShardKey();
        $this->assertSame(array('unique' => true, 'numInitialChunks' => 4096), $shardKey['options']);
        $this->assertSame(array('_id' => 1), $shardKey['keys']);
    }

    public function testGetAssociationCollectionClass()
    {
        $class = new ClassMetadataInfo('doc');
        $driver = $this->_loadDriver();
        $element = new \SimpleXmlElement('<reference-many target-document="Phonenumber" collection-class="Doctrine\\ODM\\MongoDB\\Tests\\Mapping\\PhonenumberCollection" field="phonenumbers"></reference-many>');

        /** @uses XmlDriver::setShardKey */
        $m = new \ReflectionMethod(get_class($driver), 'addReferenceMapping');
        $m->setAccessible(true);
        $m->invoke($driver, $class, $element, 'many');

        $this->assertEquals('Doctrine\\ODM\\MongoDB\\Tests\\Mapping\\PhonenumberCollection', $class->getAssociationCollectionClass('phonenumbers'));
    }
}
