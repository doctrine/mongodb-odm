<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;
use InvalidArgumentException;
use function sprintf;

class ClassMetadataFactoryTest extends BaseTest
{
    public function testGetMetadataForSingleClass()
    {
        // Self-made metadata
        $cm1 = new ClassMetadata(TestDocument1::class);
        $cm1->setCollection('group');
        // Add a mapped field
        $cm1->mapField(['fieldName' => 'name', 'type' => 'string']);
        // Add a mapped field
        $cm1->mapField(['fieldName' => 'id', 'id' => true]);
        // and a mapped association
        $cm1->mapOneEmbedded(['fieldName' => 'other', 'targetDocument' => 'Other']);
        $cm1->mapOneEmbedded(['fieldName' => 'association', 'targetDocument' => 'Other']);

        // SUT
        $cmf = new ClassMetadataFactoryTestSubject();
        $cmf->setMetadataFor(TestDocument1::class, $cm1);
        $cmf->setDocumentManager($this->dm);

        // Prechecks
        $this->assertEquals([], $cm1->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm1->inheritanceType);
        $this->assertTrue($cm1->hasField('name'));
        $this->assertCount(4, $cm1->fieldMappings);

        // Go
        $cm1 = $cmf->getMetadataFor(TestDocument1::class);

        $this->assertEquals('group', $cm1->collection);
        $this->assertEquals([], $cm1->parentClasses);
        $this->assertTrue($cm1->hasField('name'));
    }

    protected function getMockDocumentManager($driver)
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl($driver);

        $em = $this->createMock(EventManager::class);

        $dm               = new DocumentManagerMock();
        $dm->config       = $config;
        $dm->eventManager = $em;

        return $dm;
    }
}

/* Test subject class with overriden factory method for mocking purposes */
class ClassMetadataFactoryTestSubject extends ClassMetadataFactory
{
    private $_mockMetadata     = [];
    private $_requestedClasses = [];

    protected function _newClassMetadataInstance($className)
    {
        $this->_requestedClasses[] = $className;
        if (! isset($this->_mockMetadata[$className])) {
            throw new InvalidArgumentException(sprintf('No mock metadata found for class %s.', $className));
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

class Other
{
    private $name;
}
