<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Events;

class ClassMetadataTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testClassMetadataInstanceSerialization()
    {
        $cm = new ClassMetadata('Documents\CmsUser');

        // Test initial state
        $this->assertTrue(count($cm->getReflectionProperties()) == 0);
        $this->assertTrue($cm->reflClass instanceof \ReflectionClass);
        $this->assertEquals('Documents\CmsUser', $cm->name);
        $this->assertEquals('Documents\CmsUser', $cm->rootDocumentName);
        $this->assertEquals([], $cm->subClasses);
        $this->assertEquals([], $cm->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm->inheritanceType);

        // Customize state
        $cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION);
        $cm->setSubclasses(["One", "Two", "Three"]);
        $cm->setParentClasses(["UserParent"]);
        $cm->setCustomRepositoryClass("UserRepository");
        $cm->setDiscriminatorField('disc');
        $cm->mapOneEmbedded(['fieldName' => 'phonenumbers', 'targetDocument' => 'Bar']);
        $cm->setFile('customFileProperty');
        $cm->setDistance('customDistanceProperty');
        $cm->setSlaveOkay(true);
        $cm->setCollectionCapped(true);
        $cm->setCollectionMax(1000);
        $cm->setCollectionSize(500);
        $this->assertTrue(is_array($cm->getFieldMapping('phonenumbers')));
        $this->assertEquals(1, count($cm->fieldMappings));
        $this->assertEquals(1, count($cm->associationMappings));

        $serialized = serialize($cm);
        $cm = unserialize($serialized);

        // Check state
        $this->assertTrue(count($cm->getReflectionProperties()) > 0);
        $this->assertEquals('Documents', $cm->namespace);
        $this->assertTrue($cm->reflClass instanceof \ReflectionClass);
        $this->assertEquals('Documents\CmsUser', $cm->name);
        $this->assertEquals('UserParent', $cm->rootDocumentName);
        $this->assertEquals(['Documents\One', 'Documents\Two', 'Documents\Three'], $cm->subClasses);
        $this->assertEquals(['UserParent'], $cm->parentClasses);
        $this->assertEquals('Documents\UserRepository', $cm->customRepositoryClassName);
        $this->assertEquals('disc', $cm->discriminatorField);
        $this->assertTrue(is_array($cm->getFieldMapping('phonenumbers')));
        $this->assertEquals(1, count($cm->fieldMappings));
        $this->assertEquals(1, count($cm->associationMappings));
        $this->assertEquals('customFileProperty', $cm->file);
        $this->assertEquals('customDistanceProperty', $cm->distance);
        $this->assertTrue($cm->slaveOkay);
        $mapping = $cm->getFieldMapping('phonenumbers');
        $this->assertEquals('Documents\Bar', $mapping['targetDocument']);
        $this->assertTrue($cm->getCollectionCapped());
        $this->assertEquals(1000, $cm->getCollectionMax());
        $this->assertEquals(500, $cm->getCollectionSize());
    }

    public function testOwningSideAndInverseSide()
    {
        $cm = new ClassMetadataInfo('Documents\User');
        $cm->mapManyReference(['fieldName' => 'articles', 'targetDocument' => 'Documents\Article', 'inversedBy' => 'user']);
        $this->assertTrue($cm->fieldMappings['articles']['isOwningSide']);

        $cm = new ClassMetadataInfo('Documents\Article');
        $cm->mapOneReference(['fieldName' => 'user', 'targetDocument' => 'Documents\User', 'mappedBy' => 'articles']);
        $this->assertTrue($cm->fieldMappings['user']['isInverseSide']);
    }

    public function testFieldIsNullable()
    {
        $cm = new ClassMetadata('Documents\CmsUser');

        // Explicit Nullable
        $cm->mapField(['fieldName' => 'status', 'nullable' => true, 'type' => 'string', 'length' => 50]);
        $this->assertTrue($cm->isNullable('status'));

        // Explicit Not Nullable
        $cm->mapField(['fieldName' => 'username', 'nullable' => false, 'type' => 'string', 'length' => 50]);
        $this->assertFalse($cm->isNullable('username'));

        // Implicit Not Nullable
        $cm->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 50]);
        $this->assertFalse($cm->isNullable('name'), "By default a field should not be nullable.");
    }

    /**
     * @group DDC-115
     */
    public function testMapAssocationInGlobalNamespace()
    {
        require_once __DIR__."/Documents/GlobalNamespaceDocument.php";

        $cm = new ClassMetadata('DoctrineGlobal_Article');
        $cm->mapManyEmbedded([
            'fieldName' => 'author',
            'targetDocument' => 'DoctrineGlobal_User',
        ]);

        $this->assertEquals("DoctrineGlobal_User", $cm->fieldMappings['author']['targetDocument']);
    }

    public function testMapManyToManyJoinTableDefaults()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapManyEmbedded(
            [
            'fieldName' => 'groups',
            'targetDocument' => 'CmsGroup'
            ]);

        $assoc = $cm->fieldMappings['groups'];
        $this->assertTrue(is_array($assoc));
    }

    /**
     * @group DDC-115
     */
    public function testSetDiscriminatorMapInGlobalNamespace()
    {
        require_once __DIR__."/Documents/GlobalNamespaceDocument.php";

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->setDiscriminatorMap(['descr' => 'DoctrineGlobal_Article', 'foo' => 'DoctrineGlobal_User']);

        $this->assertEquals("DoctrineGlobal_Article", $cm->discriminatorMap['descr']);
        $this->assertEquals("DoctrineGlobal_User", $cm->discriminatorMap['foo']);
    }

    /**
     * @group DDC-115
     */
    public function testSetSubClassesInGlobalNamespace()
    {
        require_once __DIR__."/Documents/GlobalNamespaceDocument.php";

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->setSubclasses(['DoctrineGlobal_Article']);

        $this->assertEquals("DoctrineGlobal_Article", $cm->subClasses[0]);
    }

    public function testDuplicateFieldMapping()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $a1 = ['reference' => true, 'type' => 'many', 'fieldName' => 'foo', 'targetDocument' => 'stdClass'];
        $a2 = ['type' => 'string', 'fieldName' => 'foo'];

        $cm->mapField($a1);
        $cm->mapField($a2);

        $this->assertEquals('string', $cm->fieldMappings['foo']['type']);
    }

    public function testDuplicateColumnName_DiscriminatorColumn_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapField(['fieldName' => 'name']);

        $this->setExpectedException('Doctrine\ODM\MongoDB\Mapping\MappingException');
        $cm->setDiscriminatorField('name');
    }

    public function testDuplicateFieldName_DiscriminatorColumn2_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->setDiscriminatorField('name');

        $this->setExpectedException('Doctrine\ODM\MongoDB\Mapping\MappingException');
        $cm->mapField(['fieldName' => 'name']);
    }

    public function testDuplicateFieldAndAssocationMapping1()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapField(['fieldName' => 'name']);
        $cm->mapOneEmbedded(['fieldName' => 'name', 'targetDocument' => 'CmsUser']);

        $this->assertEquals('one', $cm->fieldMappings['name']['type']);
    }

    public function testDuplicateFieldAndAssocationMapping2()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapOneEmbedded(['fieldName' => 'name', 'targetDocument' => 'CmsUser']);
        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name', 'type' => 'string']);

        $this->assertEquals('string', $cm->fieldMappings['name']['type']);
    }
}
