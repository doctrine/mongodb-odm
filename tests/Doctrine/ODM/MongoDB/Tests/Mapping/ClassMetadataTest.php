<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Documents\Album;
use Documents\CmsUser;
use Documents\SpecialUser;
use Documents\User;
use InvalidArgumentException;

class ClassMetadataTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSlaveOkayDefault()
    {
        $cm = new ClassMetadata(TestDocument::class);

        $this->assertNull($cm->slaveOkay);
    }

    public function testDefaultDiscriminatorField()
    {
        $cm = new ClassMetadata(TestDocument::class);

        $cm->mapField(array(
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
        ));

        $cm->mapField(array(
            'fieldName' => 'assocWithTargetDocument',
            'reference' => true,
            'type' => 'one',
            'targetDocument' => TestDocument::class,
        ));

        $cm->mapField(array(
            'fieldName' => 'assocWithDiscriminatorField',
            'reference' => true,
            'type' => 'one',
            'discriminatorField' => 'type',
        ));

        $mapping = $cm->getFieldMapping('assoc');

        $this->assertEquals(
            ClassMetadata::DEFAULT_DISCRIMINATOR_FIELD, $mapping['discriminatorField'],
            'Default discriminator field is set for associations without targetDocument and discriminatorField options'
        );

        $mapping = $cm->getFieldMapping('assocWithTargetDocument');

        $this->assertArrayNotHasKey(
            'discriminatorField', $mapping,
            'Default discriminator field is not set for associations with targetDocument option'
        );

        $mapping = $cm->getFieldMapping('assocWithDiscriminatorField');

        $this->assertEquals(
            'type', $mapping['discriminatorField'],
            'Default discriminator field is not set for associations with discriminatorField option'
        );
    }

    public function testGetFieldValue()
    {
        $document = new Album('ten');
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $this->assertEquals($document->getName(), $metadata->getFieldValue($document, 'name'));
    }

    public function testGetFieldValueInitializesProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference('Documents\Album', $document->getId());
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $this->assertEquals($document->getName(), $metadata->getFieldValue($proxy, 'name'));
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $proxy);
        $this->assertTrue($proxy->__isInitialized());
    }

    public function testGetFieldValueOfIdentifierDoesNotInitializeProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference('Documents\Album', $document->getId());
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $this->assertEquals($document->getId(), $metadata->getFieldValue($proxy, 'id'));
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $proxy);
        $this->assertFalse($proxy->__isInitialized());
    }

    public function testSetFieldValue()
    {
        $document = new Album('ten');
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $metadata->setFieldValue($document, 'name', 'nevermind');

        $this->assertEquals('nevermind', $document->getName());
    }

    public function testSetFieldValueWithProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference('Documents\Album', $document->getId());
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $proxy);

        $metadata = $this->dm->getClassMetadata('Documents\Album');
        $metadata->setFieldValue($proxy, 'name', 'nevermind');

        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference('Documents\Album', $document->getId());
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $proxy);

        $this->assertEquals('nevermind', $proxy->getName());
    }

    public function testSetCustomRepositoryClass()
    {
        $cm = new ClassMetadata('Doctrine\ODM\MongoDB\Tests\Mapping\ClassMetadataTest');
        $cm->namespace = 'Doctrine\ODM\MongoDB\Tests\Mapping';

        $cm->setCustomRepositoryClass('TestCustomRepositoryClass');

        $this->assertEquals('Doctrine\ODM\MongoDB\Tests\Mapping\TestCustomRepositoryClass', $cm->customRepositoryClassName);

        $cm->setCustomRepositoryClass('Doctrine\ODM\MongoDB\Tests\Mapping\TestCustomRepositoryClass');

        $this->assertEquals('Doctrine\ODM\MongoDB\Tests\Mapping\TestCustomRepositoryClass', $cm->customRepositoryClassName);
    }

    public function testEmbeddedAssociationsAlwaysCascade()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__ . '\EmbeddedAssociationsCascadeTest');

        $this->assertTrue($class->fieldMappings['address']['isCascadeRemove']);
        $this->assertTrue($class->fieldMappings['address']['isCascadePersist']);
        $this->assertTrue($class->fieldMappings['address']['isCascadeRefresh']);
        $this->assertTrue($class->fieldMappings['address']['isCascadeMerge']);
        $this->assertTrue($class->fieldMappings['address']['isCascadeDetach']);

        $this->assertTrue($class->fieldMappings['addresses']['isCascadeRemove']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadePersist']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadeRefresh']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadeMerge']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadeDetach']);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Cascade on Doctrine\ODM\MongoDB\Tests\Mapping\EmbedWithCascadeTest::address is not allowed.
     */
    public function testEmbedWithCascadeThrowsMappingException()
    {
        $class = new ClassMetadata(__NAMESPACE__ . '\EmbedWithCascadeTest');
        $class->mapOneEmbedded(array(
            'fieldName' => 'address',
            'targetDocument' => 'Documents\Address',
            'cascade' => 'all',
        ));
    }

    public function testInvokeLifecycleCallbacksShouldRequireInstanceOfClass()
    {
        $class = $this->dm->getClassMetadata(User::class);
        $document = new TestDocument();

        $this->assertInstanceOf(TestDocument::class, $document);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Expected document class "%s"; found: "%s"', User::class, TestDocument::class));

        $class->invokeLifecycleCallbacks(Events::prePersist, $document);
    }

    public function testInvokeLifecycleCallbacksAllowsInstanceOfClass()
    {
        $class = $this->dm->getClassMetadata('\Documents\User');
        $document = new Specialuser();

        $this->assertInstanceOf('\Documents\SpecialUser', $document);

        $class->invokeLifecycleCallbacks(Events::prePersist, $document);
    }

    public function testInvokeLifecycleCallbacksShouldAllowProxyOfExactClass()
    {
        $document = new User();
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $class = $this->dm->getClassMetadata('\Documents\User');
        $proxy = $this->dm->getReference('\Documents\User', $document->getId());

        $this->assertInstanceOf('\Documents\User', $proxy);

        $class->invokeLifecycleCallbacks(Events::prePersist, $proxy);
    }

    public function testSimpleReferenceRequiresTargetDocument()
    {
        $cm = new ClassMetadata(TestDocument::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf('Target document must be specified for simple reference: %s::assoc', TestDocument::class));

        $cm->mapField(array(
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'simple' => true,
        ));
    }

    public function testSimpleAsStringReferenceRequiresTargetDocument()
    {
        $cm = new ClassMetadata(TestDocument::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf('Target document must be specified for simple reference: %s::assoc', TestDocument::class));

        $cm->mapField(array(
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'simple' => 'true',
        ));
    }

    public function testStoreAsIdReferenceRequiresTargetDocument()
    {
        $cm = new ClassMetadata(TestDocument::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf('Target document must be specified for simple reference: %s::assoc', TestDocument::class));

        $cm->mapField(array(
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID,
        ));
    }

    public function testAtomicCollectionUpdateUsageInEmbeddedDocument()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->isEmbeddedDocument = true;

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf('atomicSet collection strategy can be used only in top level document, used in %s::many', TestDocument::class));

        $cm->mapField(array(
            'fieldName' => 'many',
            'reference' => true,
            'type' => 'many',
            'strategy' => ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET,
        ));
    }

    public function testDefaultStorageStrategyOfEmbeddedDocumentFields()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->isEmbeddedDocument = true;

        $mapping = $cm->mapField(array(
            'fieldName' => 'many',
            'type' => 'many'
        ));

        self::assertEquals(CollectionHelper::DEFAULT_STRATEGY, $mapping['strategy']);
    }

    /**
     * @dataProvider provideOwningAndInversedRefsNeedTargetDocument
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function testOwningAndInversedRefsNeedTargetDocument($config)
    {
        $config = array_merge($config, array(
            'fieldName' => 'many',
            'reference' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET,
        ));

        $cm = new ClassMetadata(TestDocument::class);
        $cm->isEmbeddedDocument = true;
        $cm->mapField($config);
    }

    public function provideOwningAndInversedRefsNeedTargetDocument()
    {
        return array(
            array(array('type' => 'one', 'mappedBy' => 'post')),
            array(array('type' => 'one', 'inversedBy' => 'post')),
            array(array('type' => 'many', 'mappedBy' => 'post')),
            array(array('type' => 'many', 'inversedBy' => 'post')),
        );
    }

    public function testAddInheritedAssociationMapping()
    {
        $cm = new ClassMetadata(TestDocument::class);

        $mapping = array(
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'simple' => true,
        );

        $cm->addInheritedAssociationMapping($mapping);

        $expected = array(
            'assoc' => $mapping,
        );

        $this->assertEquals($expected, $cm->associationMappings);
    }

    public function testIdFieldsTypeMustNotBeOverridden()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->setIdentifier('id');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf('%s::id was declared an identifier and must stay this way.', TestDocument::class));

        $cm->mapField(array(
            'fieldName' => 'id',
            'type' => 'string'
        ));
    }

    public function testReferenceManySortMustNotBeUsedWithNonSetCollectionStrategy()
    {
        $cm = new ClassMetadata(TestDocument::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf('ReferenceMany\'s sort can not be used with addToSet and pushAll strategies, pushAll used in %s::ref', TestDocument::class));

        $cm->mapField(array(
            'fieldName' => 'ref',
            'reference' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
            'type' => 'many',
            'sort' => array('foo' => 1)
        ));
    }

    public function testIncrementTypeAutomaticallyAssumesIncrementStrategy()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->mapField([
            'fieldName' => 'incrementField',
            'type' => 'increment',
        ]);

        $mapping = $cm->fieldMappings['incrementField'];
        $this->assertSame(ClassMetadata::STORAGE_STRATEGY_INCREMENT, $mapping['strategy']);
    }

    public function testSetShardKeyForClassWithoutInheritance()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->setShardKey(array('id' => 'asc'));

        $shardKey = $cm->getShardKey();

        $this->assertEquals(array('id' => 1), $shardKey['keys']);
    }

    public function testSetShardKeyForClassWithSingleCollectionInheritance()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION;
        $cm->setShardKey(array('id' => 'asc'));

        $shardKey = $cm->getShardKey();

        $this->assertEquals(array('id' => 1), $shardKey['keys']);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Shard key overriding in subclass is forbidden for single collection inheritance
     */
    public function testSetShardKeyForClassWithSingleCollectionInheritanceWhichAlreadyHasIt()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->setShardKey(array('id' => 'asc'));
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION;

        $cm->setShardKey(array('foo' => 'asc'));
    }

    public function testSetShardKeyForClassWithCollPerClassInheritance()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_COLLECTION_PER_CLASS;
        $cm->setShardKey(array('id' => 'asc'));

        $shardKey = $cm->getShardKey();

        $this->assertEquals(array('id' => 1), $shardKey['keys']);
    }

    public function testIsNotShardedIfThereIsNoShardKey()
    {
        $cm = new ClassMetadata(TestDocument::class);

        $this->assertFalse($cm->isSharded());
    }

    public function testIsShardedIfThereIsAShardKey()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->setShardKey(array('id' => 'asc'));

        $this->assertTrue($cm->isSharded());
    }

    public function testEmbeddedDocumentCantHaveShardKey()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->isEmbeddedDocument = true;

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf('Embedded document can\'t have shard key: %s', TestDocument::class));

        $cm->setShardKey(array('id' => 'asc'));
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Only fields using the SET strategy can be used in the shard key
     */
    public function testNoIncrementFieldsAllowedInShardKey()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->mapField([
            'fieldName' => 'inc',
            'type' => 'int',
            'strategy' => ClassMetadata::STORAGE_STRATEGY_INCREMENT,
        ]);
        $cm->setShardKey(array('inc' => 1));
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage No multikey indexes are allowed in the shard key
     */
    public function testNoCollectionsInShardKey()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->mapField([
            'fieldName' => 'collection',
            'type' => 'collection'
        ]);
        $cm->setShardKey(array('collection' => 1));
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage No multikey indexes are allowed in the shard key
     */
    public function testNoEmbedManyInShardKey()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->mapManyEmbedded(['fieldName' => 'embedMany']);
        $cm->setShardKey(array('embedMany' => 1));
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage No multikey indexes are allowed in the shard key
     */
    public function testNoReferenceManyInShardKey()
    {
        $cm = new ClassMetadata(TestDocument::class);
        $cm->mapManyEmbedded(['fieldName' => 'referenceMany']);
        $cm->setShardKey(array('referenceMany' => 1));
    }

    public function testClassMetadataInstanceSerialization()
    {
        $cm = new ClassMetadata('Documents\CmsUser');

        // Test initial state
        $this->assertCount(0, $cm->getReflectionProperties());
        $this->assertInstanceOf(\ReflectionClass::class, $cm->reflClass);
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
        $cm->setDiscriminatorField('disc');
        $cm->mapOneEmbedded(array('fieldName' => 'phonenumbers', 'targetDocument' => 'Bar'));
        $cm->setFile('customFileProperty');
        $cm->setDistance('customDistanceProperty');
        $cm->setSlaveOkay(true);
        $cm->setShardKey(array('_id' => '1'));
        $cm->setCollectionCapped(true);
        $cm->setCollectionMax(1000);
        $cm->setCollectionSize(500);
        $this->assertInternalType('array', $cm->getFieldMapping('phonenumbers'));
        $this->assertCount(1, $cm->fieldMappings);
        $this->assertCount(1, $cm->associationMappings);

        $serialized = serialize($cm);
        $cm = unserialize($serialized);

        // Check state
        $this->assertGreaterThan(0, $cm->getReflectionProperties());
        $this->assertEquals('Documents', $cm->namespace);
        $this->assertInstanceOf(\ReflectionClass::class, $cm->reflClass);
        $this->assertEquals('Documents\CmsUser', $cm->name);
        $this->assertEquals('UserParent', $cm->rootDocumentName);
        $this->assertEquals(array('Documents\One', 'Documents\Two', 'Documents\Three'), $cm->subClasses);
        $this->assertEquals(array('UserParent'), $cm->parentClasses);
        $this->assertEquals('Documents\UserRepository', $cm->customRepositoryClassName);
        $this->assertEquals('disc', $cm->discriminatorField);
        $this->assertInternalType('array', $cm->getFieldMapping('phonenumbers'));
        $this->assertCount(1, $cm->fieldMappings);
        $this->assertCount(1, $cm->associationMappings);
        $this->assertEquals('customFileProperty', $cm->file);
        $this->assertEquals('customDistanceProperty', $cm->distance);
        $this->assertTrue($cm->slaveOkay);
        $this->assertEquals(array('keys' => array('_id' => 1), 'options' => array()), $cm->getShardKey());
        $mapping = $cm->getFieldMapping('phonenumbers');
        $this->assertEquals('Documents\Bar', $mapping['targetDocument']);
        $this->assertTrue($cm->getCollectionCapped());
        $this->assertEquals(1000, $cm->getCollectionMax());
        $this->assertEquals(500, $cm->getCollectionSize());
    }

    public function testOwningSideAndInverseSide()
    {
        $cm = new ClassMetadata('Documents\User');
        $cm->mapManyReference(array('fieldName' => 'articles', 'targetDocument' => 'Documents\Article', 'inversedBy' => 'user'));
        $this->assertTrue($cm->fieldMappings['articles']['isOwningSide']);

        $cm = new ClassMetadata('Documents\Article');
        $cm->mapOneReference(array('fieldName' => 'user', 'targetDocument' => 'Documents\User', 'mappedBy' => 'articles'));
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
        $this->assertInternalType('array', $assoc);
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

    public function testDuplicateFieldMapping()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $a1 = array('reference' => true, 'type' => 'many', 'fieldName' => 'name', 'targetDocument' => TestDocument::class);
        $a2 = array('type' => 'string', 'fieldName' => 'name');

        $cm->mapField($a1);
        $cm->mapField($a2);

        $this->assertEquals('string', $cm->fieldMappings['name']['type']);
    }

    public function testDuplicateColumnName_DiscriminatorColumn_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapField(array('fieldName' => 'name'));

        $this->expectException(\Doctrine\ODM\MongoDB\Mapping\MappingException::class);
        $cm->setDiscriminatorField('name');
    }

    public function testDuplicateFieldName_DiscriminatorColumn2_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->setDiscriminatorField('name');

        $this->expectException(\Doctrine\ODM\MongoDB\Mapping\MappingException::class);
        $cm->mapField(array('fieldName' => 'name'));
    }

    public function testDuplicateFieldAndAssocationMapping1()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapField(array('fieldName' => 'name'));
        $cm->mapOneEmbedded(array('fieldName' => 'name', 'targetDocument' => 'CmsUser'));

        $this->assertEquals('one', $cm->fieldMappings['name']['type']);
    }

    public function testDuplicateFieldAndAssocationMapping2()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapOneEmbedded(array('fieldName' => 'name', 'targetDocument' => 'CmsUser'));
        $cm->mapField(array('fieldName' => 'name', 'columnName' => 'name', 'type' => 'string'));

        $this->assertEquals('string', $cm->fieldMappings['name']['type']);
    }

    /**
     * @expectedException \ReflectionException
     */
    public function testMapNotExistingFieldThrowsException()
    {
        $cm = new ClassMetadata('Documents\CmsUser');
        $cm->mapField(array('fieldName' => 'namee', 'columnName' => 'name', 'type' => 'string'));
    }

    /**
     * @param ClassMetadata $cm
     * @dataProvider dataProviderMetadataClasses
     */
    public function testEmbeddedDocumentWithDiscriminator(ClassMetadata $cm)
    {
        $cm->setDiscriminatorField('discriminator');
        $cm->setDiscriminatorValue('discriminatorValue');

        $serialized = serialize($cm);
        $cm = unserialize($serialized);

        $this->assertSame('discriminator', $cm->discriminatorField);
        $this->assertSame('discriminatorValue', $cm->discriminatorValue);
    }

    public static function dataProviderMetadataClasses()
    {
        $document = new ClassMetadata(CmsUser::class);

        $embeddedDocument = new ClassMetadata(CmsUser::class);
        $embeddedDocument->isEmbeddedDocument = true;

        $mappedSuperclass = new ClassMetadata(CmsUser::class);
        $mappedSuperclass->isMappedSuperclass = true;

        return [
            'document' => [$document],
            'mappedSuperclass' => [$mappedSuperclass],
            'embeddedDocument' => [$embeddedDocument],
        ];
    }
}

class TestDocument
{
    public $assoc;

    public $assocWithTargetDocument;

    public $assocWithDiscriminatorField;

    public $many;

    public $incrementField;

    public $inc;

    public $collection;

    public $embedMany;

    public $referenceMany;

    public $articles;
}

class TestCustomRepositoryClass extends DocumentRepository
{
}

class EmbedWithCascadeTest
{
    public $address;
}

/** @ODM\Document */
class EmbeddedAssociationsCascadeTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="Documents\Address") */
    public $address;

    /** @ODM\EmbedOne(targetDocument="Documents\Address") */
    public $addresses;
}
