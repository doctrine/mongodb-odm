<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;

class ClassMetadataFactoryTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGetMetadataForSingleClass()
    {
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
        $cmf->setMetadataFor('Doctrine\ODM\MongoDB\Tests\Mapping\TestDocument1', $cm1);

        // Prechecks
        $this->assertEquals(array(), $cm1->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm1->inheritanceType);
        $this->assertTrue($cm1->hasField('name'));
        $this->assertCount(4, $cm1->fieldMappings);

        // Go
        $cm1 = $cmf->getMetadataFor('Doctrine\ODM\MongoDB\Tests\Mapping\TestDocument1');

        $this->assertEquals('group', $cm1->collection);
        $this->assertEquals(array(), $cm1->parentClasses);
        $this->assertTrue($cm1->hasField('name'));
    }

    public function testHasGetMetadata_NamespaceSeperatorIsNotNormalized()
    {
        require_once __DIR__."/Documents/GlobalNamespaceDocument.php";

        $driver = AnnotationDriver::create(__DIR__ . '/Documents');

        $dm = $this->getMockDocumentManager($driver);

        $cmf = new ClassMetadataFactory();
        $cmf->setConfiguration($dm->getConfiguration());
        $cmf->setDocumentManager($dm);

        $m1 = $cmf->getMetadataFor("DoctrineGlobal_Article");
        $h1 = $cmf->hasMetadataFor("DoctrineGlobal_Article");
        $h2 = $cmf->hasMetadataFor("\DoctrineGlobal_Article");
        $m2 = $cmf->getMetadataFor("\DoctrineGlobal_Article");

        $this->assertNotSame($m1, $m2);
        $this->assertFalse($h2);
        $this->assertTrue($h1);
    }

    public function testMetadataNotFoundTriggersEventAndReturnsFallbackMetadata()
    {
        $customMetadata = new ClassMetadata(TestDocument1::class);

        $listener = new MetadataNotFoundListener();
        $listener->addFallbackMetadata(TestDocument1::class, $customMetadata);

        $dm = $this->createTestDocumentManager();
        $dm->getEventManager()->addEventListener(Events::onClassMetadataNotFound, $listener);

        $metadata = $dm->getClassMetadata(TestDocument1::class);
        self::assertSame($customMetadata, $metadata);
    }

    protected function getMockDocumentManager($driver)
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl($driver);

        $em = $this->createMock('Doctrine\Common\EventManager');

        $dm = new DocumentManagerMock();
        $dm->config = $config;
        $dm->eventManager = $em;

        return $dm;
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

class MetadataNotFoundListener
{
    /**
     * @var ClassMetadata[]
     */
    private $metadata = [];

    public function addFallbackMetadata($className, ClassMetadata $classMetadata)
    {
        $this->metadata[$className] = $classMetadata;
    }

    public function onClassMetadataNotFound(OnClassMetadataNotFoundEventArgs $eventArgs)
    {
        if (! isset($this->metadata[$eventArgs->getClassName()])) {
            return;
        }

        $eventArgs->setFoundMetadata($this->metadata[$eventArgs->getClassName()]);
    }
}

class TestDocument1
{
    private $id;
    private $name;
    private $other;
    private $association;
}
