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
        $this->assertEquals(array(), $cm->subClasses);
        $this->assertEquals(array(), $cm->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm->inheritanceType);

        // Customize state
        $cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION);
        $cm->setSubclasses(array("One", "Two", "Three"));
        $cm->setParentClasses(array("UserParent"));
        $cm->setCustomRepositoryClass("UserRepository");
        $cm->setDiscriminatorField(array('name' => 'disc'));
        $cm->mapOneEmbedded(array('fieldName' => 'phonenumbers', 'targetDocument' => 'Bar'));
        $cm->setFile('customFileProperty');
        $this->assertTrue(is_array($cm->getFieldMapping('phonenumbers')));
        $this->assertEquals(1, count($cm->fieldMappings));

        $serialized = serialize($cm);
        $cm = unserialize($serialized);

        // Check state
        $this->assertTrue(count($cm->getReflectionProperties()) > 0);
        $this->assertEquals('Documents', $cm->namespace);
        $this->assertTrue($cm->reflClass instanceof \ReflectionClass);
        $this->assertEquals('Documents\CmsUser', $cm->name);
        $this->assertEquals('UserParent', $cm->rootDocumentName);
        $this->assertEquals(array('Documents\One', 'Documents\Two', 'Documents\Three'), $cm->subClasses);
        $this->assertEquals(array('UserParent'), $cm->parentClasses);
        $this->assertEquals('UserRepository', $cm->customRepositoryClassName);
        $this->assertEquals(array('name' => 'disc'), $cm->discriminatorField);
        $this->assertTrue(is_array($cm->getFieldMapping('phonenumbers')));
        $this->assertEquals(1, count($cm->fieldMappings));
        $this->assertEquals('customFileProperty', $cm->file);
        $mapping = $cm->getFieldMapping('phonenumbers');
        $this->assertEquals('Documents\Bar', $mapping['targetDocument']);
    }

    public function testOwningSideAndInverseSide()
    {
        $cm = new ClassMetadataInfo('Documents\User');
        $cm->mapManyReference(array('fieldName' => 'articles', 'inversedBy' => 'user'));
        $this->assertTrue($cm->fieldMappings['articles']['isOwningSide']);

        $cm = new ClassMetadataInfo('Documents\Article');
        $cm->mapOneReference(array('fieldName' => 'user', 'mappedBy' => 'articles'));
        $this->assertTrue($cm->fieldMappings['user']['isInverseSide']);
    }

    public function testFieldIsNullable()
    {
        $cm = new ClassMetadata('Documents\CmsUser');

        // Explicit Nullable
        $cm->mapField(array('fieldName' => 'status', 'nullable' => true, 'type' => 'string', 'length' => 50));
        $this->assertTrue($cm->isNullable('status'));

        // Explicit Not Nullable
        $cm->mapField(array('fieldName' => 'username', 'nullable' => false, 'type' => 'string', 'length' => 50));
        $this->assertFalse($cm->isNullable('username'));

        // Implicit Not Nullable
        $cm->mapField(array('fieldName' => 'name', 'type' => 'string', 'length' => 50));
        $this->assertFalse($cm->isNullable('name'), "By default a field should not be nullable.");
    }

    /**
     * @group DDC-115
     */
    public function testMapAssocationInGlobalNamespace()
    {
        require_once __DIR__."/Documents/GlobalNamespaceDocument.php";

        $cm = new ClassMetadata('DoctrineGlobal_Article');
        $cm->mapManyEmbedded(array(
            'fieldName' => 'author',
            'targetDocument' => 'DoctrineGlobal_User',
        ));

        $this->assertEquals("DoctrineGlobal_User", $cm->fieldMappings['author']['targetDocument']);
    }
    
    public function testMapManyToManyJoinTableDefaults()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapManyEmbedded(
            array(
            'fieldName' => 'groups',
            'targetDocument' => 'CmsGroup'
        ));
        
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
        $cm->setDiscriminatorMap(array('descr' => 'DoctrineGlobal_Article', 'foo' => 'DoctrineGlobal_User'));

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
        $cm->setSubclasses(array('DoctrineGlobal_Article'));

        $this->assertEquals("DoctrineGlobal_Article", $cm->subClasses[0]);
    }

    public function testDuplicateFieldMappingException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $a1 = array('reference' => true, 'type' => 'many', 'fieldName' => 'foo', 'targetDocument' => 'stdClass');
        $a2 = array('reference' => true, 'type' => 'many', 'fieldName' => 'foo', 'targetDocument' => 'stdClass');

        $cm->mapField($a1);
        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException');
        $cm->mapField($a2);
    }

    public function testDuplicateColumnName_DiscriminatorColumn_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapField(array('fieldName' => 'name'));

        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException');
        $cm->setDiscriminatorField(array('name' => 'name'));
    }

    public function testDuplicateFieldName_DiscriminatorColumn2_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->setDiscriminatorField(array('name' => 'name'));

        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException');
        $cm->mapField(array('fieldName' => 'name'));
    }

    public function testDuplicateFieldAndAssocationMapping1_ThrowsException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapField(array('fieldName' => 'name'));

        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException');
        $cm->mapOneEmbedded(array('fieldName' => 'name', 'targetDocument' => 'CmsUser'));
    }

    public function testDuplicateFieldAndAssocationMapping2_ThrowsException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapOneEmbedded(array('fieldName' => 'name', 'targetDocument' => 'CmsUser'));

        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException');
        $cm->mapField(array('fieldName' => 'name', 'columnName' => 'name'));
    }
}