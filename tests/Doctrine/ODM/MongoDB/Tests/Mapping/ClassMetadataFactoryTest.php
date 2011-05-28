<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Tests\Mocks\MetadataDriverMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\ConnectionMock;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Common\EventManager;

class ClassMetadataFactoryTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGetMetadataForSingleClass()
    {
        $mockDriver = new MetadataDriverMock();
        $documentManager = $this->_createDocumentManager($mockDriver);

        // Self-made metadata
        $cm1 = new ClassMetadata('Doctrine\ODM\MongoDB\Tests\Mapping\TestDocument1');
        $cm1->setCollection('group');
        // Add a mapped field
        $cm1->mapField(array('fieldName' => 'name', 'type' => 'string'));
        // Add a mapped field
        $cm1->mapField(array('fieldName' => 'id', 'id' => true));
        // and a mapped association
        $cm1->mapOneEmbedded(array('fieldName' => 'other', 'targetDocument' => 'Other'));
        $cm1->mapOneEmbedded(array('fieldName' => 'association', 'targetDocument' => 'Other'));

        // SUT
        $cmf = new ClassMetadataFactoryTestSubject();
        $cmf->setDocumentManager($documentManager);
        $cmf->setMetadataFor('Doctrine\ODM\MongoDB\Tests\Mapping\TestDocument1', $cm1);

        // Prechecks
        $this->assertEquals(array(), $cm1->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm1->inheritanceType);
        $this->assertTrue($cm1->hasField('name'));
        $this->assertEquals(4, count($cm1->fieldMappings));

        // Go
        $cm1 = $cmf->getMetadataFor('Doctrine\ODM\MongoDB\Tests\Mapping\TestDocument1');

        $this->assertEquals('group', $cm1->collection);
        $this->assertEquals(array(), $cm1->parentClasses);
        $this->assertTrue($cm1->hasField('name'));
    }

    public function testHasGetMetadata_NamespaceSeperatorIsNotNormalized()
    {
        require_once __DIR__."/Documents/GlobalNamespaceDocument.php";

        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $metadataDriver = new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader);
        $metadataDriver->addPaths(array(__DIR__ . '/../../Documents/'));

        $documentManager = $this->_createDocumentManager($metadataDriver);

        $mf = $documentManager->getMetadataFactory();
        $m1 = $mf->getMetadataFor("DoctrineGlobal_Article");
        $h1 = $mf->hasMetadataFor("DoctrineGlobal_Article");
        $h2 = $mf->hasMetadataFor("\DoctrineGlobal_Article");
        $m2 = $mf->getMetadataFor("\DoctrineGlobal_Article");

        $this->assertNotSame($m1, $m2);
        $this->assertFalse($h2);
        $this->assertTrue($h1);
    }

    protected function _createDocumentManager($metadataDriver)
    {
        $connMock = new ConnectionMock();
        $config = new \Doctrine\ODM\MongoDB\Configuration();

        $config->setProxyDir(__DIR__ . '/../../Proxies');
        $config->setProxyNamespace('Doctrine\ODM\MongoDB\Tests\Proxies');

        $config->setHydratorDir(__DIR__ . '/../../Hydrators');
        $config->setHydratorNamespace('Doctrine\ODM\MongoDB\Tests\Hydrators');

        $eventManager = new EventManager();
        $mockDriver = new MetadataDriverMock();
        $config->setMetadataDriverImpl($metadataDriver);

        return DocumentManagerMock::create($connMock, $config, $eventManager);
    }
}

/* Test subject class with overriden factory method for mocking purposes */
class ClassMetadataFactoryTestSubject extends \Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
{
    private $_mockMetadata = array();
    private $_requestedClasses = array();

    /** @override */
    protected function _newClassMetadataInstance($className)
    {
        $this->_requestedClasses[] = $className;
        if ( ! isset($this->_mockMetadata[$className])) {
            throw new InvalidArgumentException("No mock metadata found for class $className.");
        }
        return $this->_mockMetadata[$className];
    }

    public function setMetadataForClass($className, $metadata)
    {
        $this->_mockMetadata[$className] = $metadata;
    }

    public function getRequestedClasses()
    {
        return $this->_requestedClasses;
    }
}

class TestDocument1
{
    private $id;
    private $name;
    private $other;
    private $association;
}