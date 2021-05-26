<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Documents\Account;
use Documents\Address;
use Documents\Album;
use Documents\Bars\Bar;
use Documents\CmsUser;
use Documents\SpecialUser;
use Documents\User;
use Documents\UserRepository;
use InvalidArgumentException;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionClass;
use ReflectionException;
use stdClass;

use function array_merge;
use function get_class;
use function MongoDB\BSON\fromJSON;
use function MongoDB\BSON\toPHP;
use function serialize;
use function unserialize;

class ClassMetadataTest extends BaseTest
{
    public function testClassMetadataInstanceSerialization()
    {
        $cm = new ClassMetadata(CmsUser::class);

        // Test initial state
        $this->assertCount(0, $cm->getReflectionProperties());
        $this->assertInstanceOf(ReflectionClass::class, $cm->reflClass);
        $this->assertEquals(CmsUser::class, $cm->name);
        $this->assertEquals(CmsUser::class, $cm->rootDocumentName);
        $this->assertEquals([], $cm->subClasses);
        $this->assertEquals([], $cm->parentClasses);
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm->inheritanceType);

        // Customize state
        $cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION);
        $cm->setSubclasses(['One', 'Two', 'Three']);
        $cm->setParentClasses(['UserParent']);
        $cm->setCustomRepositoryClass(UserRepository::class);
        $cm->setDiscriminatorField('disc');
        $cm->mapOneEmbedded(['fieldName' => 'phonenumbers', 'targetDocument' => Bar::class]);
        $cm->setShardKey(['_id' => '1']);
        $cm->setCollectionCapped(true);
        $cm->setCollectionMax(1000);
        $cm->setCollectionSize(500);
        $cm->setLockable(true);
        $cm->setLockField('lock');
        $cm->setVersioned(true);
        $cm->setVersionField('version');
        $validatorJson = '{ "$and": [ { "email": { "$regex": { "$regularExpression" : { "pattern": "@mongodb\\\\.com$", "options": "" } } } } ] }';
        /** @psalm-suppress TooFewArguments */
        $cm->setValidator(toPHP(fromJSON($validatorJson)));
        $cm->setValidationAction(ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN);
        $cm->setValidationLevel(ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF);
        $this->assertIsArray($cm->getFieldMapping('phonenumbers'));
        $this->assertCount(1, $cm->fieldMappings);
        $this->assertCount(1, $cm->associationMappings);

        $serialized = serialize($cm);
        $cm         = unserialize($serialized);

        // Check state
        $this->assertGreaterThan(0, $cm->getReflectionProperties());
        $this->assertInstanceOf(ReflectionClass::class, $cm->reflClass);
        $this->assertEquals(CmsUser::class, $cm->name);
        $this->assertEquals('UserParent', $cm->rootDocumentName);
        $this->assertEquals(['One', 'Two', 'Three'], $cm->subClasses);
        $this->assertEquals(['UserParent'], $cm->parentClasses);
        $this->assertEquals(UserRepository::class, $cm->customRepositoryClassName);
        $this->assertEquals('disc', $cm->discriminatorField);
        $this->assertIsArray($cm->getFieldMapping('phonenumbers'));
        $this->assertCount(1, $cm->fieldMappings);
        $this->assertCount(1, $cm->associationMappings);
        $this->assertEquals(['keys' => ['_id' => 1], 'options' => []], $cm->getShardKey());
        $mapping = $cm->getFieldMapping('phonenumbers');
        $this->assertEquals(Bar::class, $mapping['targetDocument']);
        $this->assertTrue($cm->getCollectionCapped());
        $this->assertEquals(1000, $cm->getCollectionMax());
        $this->assertEquals(500, $cm->getCollectionSize());
        $this->assertEquals(true, $cm->isLockable);
        $this->assertEquals('lock', $cm->lockField);
        $this->assertEquals(true, $cm->isVersioned);
        $this->assertEquals('version', $cm->versionField);
        /** @psalm-suppress TooFewArguments */
        $this->assertEquals(toPHP(fromJSON($validatorJson)), $cm->getValidator());
        $this->assertEquals(ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN, $cm->getValidationAction());
        $this->assertEquals(ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF, $cm->getValidationLevel());
    }

    public function testOwningSideAndInverseSide()
    {
        $cm = new ClassMetadata(User::class);
        $cm->mapOneReference(['fieldName' => 'account', 'targetDocument' => Account::class, 'inversedBy' => 'user']);
        $this->assertTrue($cm->fieldMappings['account']['isOwningSide']);

        $cm = new ClassMetadata(Account::class);
        $cm->mapOneReference(['fieldName' => 'user', 'targetDocument' => Account::class, 'mappedBy' => 'account']);
        $this->assertTrue($cm->fieldMappings['user']['isInverseSide']);
    }

    public function testFieldIsNullable()
    {
        $cm = new ClassMetadata(CmsUser::class);

        // Explicit Nullable
        $cm->mapField(['fieldName' => 'status', 'nullable' => true, 'type' => 'string', 'length' => 50]);
        $this->assertTrue($cm->isNullable('status'));

        // Explicit Not Nullable
        $cm->mapField(['fieldName' => 'username', 'nullable' => false, 'type' => 'string', 'length' => 50]);
        $this->assertFalse($cm->isNullable('username'));

        // Implicit Not Nullable
        $cm->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 50]);
        $this->assertFalse($cm->isNullable('name'), 'By default a field should not be nullable.');
    }

    /**
     * @group DDC-115
     */
    public function testMapAssocationInGlobalNamespace()
    {
        require_once __DIR__ . '/Documents/GlobalNamespaceDocument.php';

        $cm = new ClassMetadata('DoctrineGlobal_Article');
        $cm->mapManyEmbedded([
            'fieldName' => 'author',
            'targetDocument' => 'DoctrineGlobal_User',
        ]);

        $this->assertEquals('DoctrineGlobal_User', $cm->fieldMappings['author']['targetDocument']);
    }

    public function testMapManyToManyJoinTableDefaults()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapManyEmbedded(
            [
                'fieldName' => 'groups',
                'targetDocument' => 'CmsGroup',
            ]
        );

        $assoc = $cm->fieldMappings['groups'];
        $this->assertIsArray($assoc);
    }

    public function testGetAssociationTargetClassWithoutTargetDocument()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapManyEmbedded(
            [
                'fieldName' => 'groups',
                'targetDocument' => null,
            ]
        );

        $this->assertNull($cm->getAssociationTargetClass('groups'));
    }

    /**
     * @group DDC-115
     */
    public function testSetDiscriminatorMapInGlobalNamespace()
    {
        require_once __DIR__ . '/Documents/GlobalNamespaceDocument.php';

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->setDiscriminatorMap(['descr' => 'DoctrineGlobal_Article', 'foo' => 'DoctrineGlobal_User']);

        $this->assertEquals('DoctrineGlobal_Article', $cm->discriminatorMap['descr']);
        $this->assertEquals('DoctrineGlobal_User', $cm->discriminatorMap['foo']);
    }

    /**
     * @group DDC-115
     */
    public function testSetSubClassesInGlobalNamespace()
    {
        require_once __DIR__ . '/Documents/GlobalNamespaceDocument.php';

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->setSubclasses(['DoctrineGlobal_Article']);

        $this->assertEquals('DoctrineGlobal_Article', $cm->subClasses[0]);
    }

    public function testDuplicateFieldMapping()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $a1 = ['reference' => true, 'type' => 'many', 'fieldName' => 'name', 'targetDocument' => 'stdClass'];
        $a2 = ['type' => 'string', 'fieldName' => 'name'];

        $cm->mapField($a1);
        $cm->mapField($a2);

        $this->assertEquals('string', $cm->fieldMappings['name']['type']);
    }

    public function testDuplicateColumnNameDiscriminatorColumnThrowsMappingException()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapField(['fieldName' => 'name', 'type' => Type::STRING]);

        $this->expectException(MappingException::class);
        $cm->setDiscriminatorField('name');
    }

    public function testDuplicateFieldNameDiscriminatorColumn2ThrowsMappingException()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->setDiscriminatorField('name');

        $this->expectException(MappingException::class);
        $cm->mapField(['fieldName' => 'name', 'type' => Type::STRING]);
    }

    public function testDuplicateFieldAndAssocationMapping1()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapField(['fieldName' => 'name', 'type' => Type::STRING]);
        $cm->mapOneEmbedded(['fieldName' => 'name', 'targetDocument' => 'CmsUser']);

        $this->assertEquals('one', $cm->fieldMappings['name']['type']);
    }

    public function testDuplicateFieldAndAssocationMapping2()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapOneEmbedded(['fieldName' => 'name', 'targetDocument' => 'CmsUser']);
        $cm->mapField(['fieldName' => 'name', 'columnName' => 'name', 'type' => 'string']);

        $this->assertEquals('string', $cm->fieldMappings['name']['type']);
    }

    public function testMapNotExistingFieldThrowsException()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $this->expectException(ReflectionException::class);
        $cm->mapField(['fieldName' => 'namee', 'columnName' => 'name', 'type' => 'string']);
    }

    /**
     * @dataProvider dataProviderMetadataClasses
     */
    public function testEmbeddedDocumentWithDiscriminator(ClassMetadata $cm)
    {
        $cm->setDiscriminatorField('discriminator');
        $cm->setDiscriminatorValue('discriminatorValue');

        $serialized = serialize($cm);
        $cm         = unserialize($serialized);

        $this->assertSame('discriminator', $cm->discriminatorField);
        $this->assertSame('discriminatorValue', $cm->discriminatorValue);
    }

    public static function dataProviderMetadataClasses()
    {
        $document = new ClassMetadata(CmsUser::class);

        $embeddedDocument                     = new ClassMetadata(CmsUser::class);
        $embeddedDocument->isEmbeddedDocument = true;

        $mappedSuperclass                     = new ClassMetadata(CmsUser::class);
        $mappedSuperclass->isMappedSuperclass = true;

        return [
            'document' => [$document],
            'mappedSuperclass' => [$mappedSuperclass],
            'embeddedDocument' => [$embeddedDocument],
        ];
    }

    public function testDefaultDiscriminatorField()
    {
        $object = new class {
            public $assoc;
            public $assocWithTargetDocument;
            public $assocWithDiscriminatorField;
        };

        $cm = new ClassMetadata(get_class($object));

        $cm->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
        ]);

        $cm->mapField([
            'fieldName' => 'assocWithTargetDocument',
            'reference' => true,
            'type' => 'one',
            'targetDocument' => 'stdClass',
        ]);

        $cm->mapField([
            'fieldName' => 'assocWithDiscriminatorField',
            'reference' => true,
            'type' => 'one',
            'discriminatorField' => 'type',
        ]);

        $mapping = $cm->getFieldMapping('assoc');

        $this->assertEquals(
            ClassMetadata::DEFAULT_DISCRIMINATOR_FIELD,
            $mapping['discriminatorField'],
            'Default discriminator field is set for associations without targetDocument and discriminatorField options'
        );

        $mapping = $cm->getFieldMapping('assocWithTargetDocument');

        $this->assertArrayNotHasKey(
            'discriminatorField',
            $mapping,
            'Default discriminator field is not set for associations with targetDocument option'
        );

        $mapping = $cm->getFieldMapping('assocWithDiscriminatorField');

        $this->assertEquals(
            'type',
            $mapping['discriminatorField'],
            'Default discriminator field is not set for associations with discriminatorField option'
        );
    }

    public function testGetFieldValue()
    {
        $document = new Album('ten');
        $metadata = $this->dm->getClassMetadata(Album::class);

        $this->assertEquals($document->getName(), $metadata->getFieldValue($document, 'name'));
    }

    public function testGetFieldValueInitializesProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy    = $this->dm->getReference(Album::class, $document->getId());
        $metadata = $this->dm->getClassMetadata(Album::class);

        $this->assertEquals($document->getName(), $metadata->getFieldValue($proxy, 'name'));
        $this->assertInstanceOf(GhostObjectInterface::class, $proxy);
        $this->assertTrue($proxy->isProxyInitialized());
    }

    public function testGetFieldValueOfIdentifierDoesNotInitializeProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy    = $this->dm->getReference(Album::class, $document->getId());
        $metadata = $this->dm->getClassMetadata(Album::class);

        $this->assertEquals($document->getId(), $metadata->getFieldValue($proxy, 'id'));
        $this->assertInstanceOf(GhostObjectInterface::class, $proxy);
        $this->assertFalse($proxy->isProxyInitialized());
    }

    public function testSetFieldValue()
    {
        $document = new Album('ten');
        $metadata = $this->dm->getClassMetadata(Album::class);

        $metadata->setFieldValue($document, 'name', 'nevermind');

        $this->assertEquals('nevermind', $document->getName());
    }

    public function testSetFieldValueWithProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference(Album::class, $document->getId());
        $this->assertInstanceOf(GhostObjectInterface::class, $proxy);

        $metadata = $this->dm->getClassMetadata(Album::class);
        $metadata->setFieldValue($proxy, 'name', 'nevermind');

        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference(Album::class, $document->getId());
        $this->assertInstanceOf(GhostObjectInterface::class, $proxy);

        $this->assertEquals('nevermind', $proxy->getName());
    }

    public function testSetCustomRepositoryClass()
    {
        $cm            = new ClassMetadata('Doctrine\ODM\MongoDB\Tests\Mapping\ClassMetadataTest');
        $cm->namespace = 'Doctrine\ODM\MongoDB\Tests\Mapping';

        $cm->setCustomRepositoryClass('TestCustomRepositoryClass');

        $this->assertEquals('TestCustomRepositoryClass', $cm->customRepositoryClassName);

        $cm->setCustomRepositoryClass('Doctrine\ODM\MongoDB\Tests\Mapping\TestCustomRepositoryClass');

        $this->assertEquals('Doctrine\ODM\MongoDB\Tests\Mapping\TestCustomRepositoryClass', $cm->customRepositoryClassName);
    }

    public function testEmbeddedAssociationsAlwaysCascade()
    {
        $class = $this->dm->getClassMetadata(EmbeddedAssociationsCascadeTest::class);

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

    public function testEmbedWithCascadeThrowsMappingException()
    {
        $class = new ClassMetadata(EmbedWithCascadeTest::class);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Cascade on Doctrine\ODM\MongoDB\Tests\Mapping\EmbedWithCascadeTest::address is not allowed.'
        );
        $class->mapOneEmbedded([
            'fieldName' => 'address',
            'targetDocument' => Address::class,
            'cascade' => 'all',
        ]);
    }

    public function testInvokeLifecycleCallbacksShouldRequireInstanceOfClass()
    {
        $class    = $this->dm->getClassMetadata(User::class);
        $document = new stdClass();

        $this->assertInstanceOf('\stdClass', $document);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected document class "Documents\User"; found: "stdClass"');
        $class->invokeLifecycleCallbacks(Events::prePersist, $document);
    }

    public function testInvokeLifecycleCallbacksAllowsInstanceOfClass()
    {
        $class    = $this->dm->getClassMetadata(User::class);
        $document = new SpecialUser();

        $this->assertInstanceOf(SpecialUser::class, $document);

        $class->invokeLifecycleCallbacks(Events::prePersist, $document);
    }

    public function testInvokeLifecycleCallbacksShouldAllowProxyOfExactClass()
    {
        $document = new User();
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $class = $this->dm->getClassMetadata(User::class);
        $proxy = $this->dm->getReference(User::class, $document->getId());

        $this->assertInstanceOf(User::class, $proxy);

        $class->invokeLifecycleCallbacks(Events::prePersist, $proxy);
    }

    public function testSimpleReferenceRequiresTargetDocument()
    {
        $cm = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Target document must be specified for identifier reference: stdClass::assoc');
        $cm->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID,
        ]);
    }

    public function testSimpleAsStringReferenceRequiresTargetDocument()
    {
        $cm = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Target document must be specified for identifier reference: stdClass::assoc');
        $cm->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID,
        ]);
    }

    /**
     * @dataProvider provideRepositoryMethodCanNotBeCombinedWithSkipLimitAndSort
     */
    public function testRepositoryMethodCanNotBeCombinedWithSkipLimitAndSort($prop, $value)
    {
        $cm = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            '\'repositoryMethod\' used on \'assoc\' in class \'stdClass\' can not be combined with skip, ' .
            'limit or sort.'
        );
        $cm->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'many',
            'targetDocument' => 'stdClass',
            'repositoryMethod' => 'fetch',
            $prop => $value,
        ]);
    }

    public function provideRepositoryMethodCanNotBeCombinedWithSkipLimitAndSort()
    {
        yield ['skip', 5];
        yield ['limit', 5];
        yield ['sort', ['time' => 1]];
    }

    public function testStoreAsIdReferenceRequiresTargetDocument()
    {
        $cm = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Target document must be specified for identifier reference: stdClass::assoc');
        $cm->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID,
        ]);
    }

    public function testAtomicCollectionUpdateUsageInEmbeddedDocument()
    {
        $object = new class {
            public $many;
        };

        $cm                     = new ClassMetadata(get_class($object));
        $cm->isEmbeddedDocument = true;

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('atomicSet collection strategy can be used only in top level document, used in');
        $cm->mapField([
            'fieldName' => 'many',
            'reference' => true,
            'type' => 'many',
            'strategy' => ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET,
        ]);
    }

    public function testDefaultStorageStrategyOfEmbeddedDocumentFields()
    {
        $object = new class {
            public $many;
        };

        $cm                     = new ClassMetadata(get_class($object));
        $cm->isEmbeddedDocument = true;

        $mapping = $cm->mapField([
            'fieldName' => 'many',
            'type' => 'many',
        ]);

        self::assertEquals(CollectionHelper::DEFAULT_STRATEGY, $mapping['strategy']);
    }

    /**
     * @dataProvider provideOwningAndInversedRefsNeedTargetDocument
     */
    public function testOwningAndInversedRefsNeedTargetDocument($config)
    {
        $config = array_merge($config, [
            'fieldName' => 'many',
            'reference' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET,
        ]);

        $cm                     = new ClassMetadata('stdClass');
        $cm->isEmbeddedDocument = true;
        $this->expectException(MappingException::class);
        $cm->mapField($config);
    }

    public function provideOwningAndInversedRefsNeedTargetDocument()
    {
        return [
            [['type' => 'one', 'mappedBy' => 'post']],
            [['type' => 'one', 'inversedBy' => 'post']],
            [['type' => 'many', 'mappedBy' => 'post']],
            [['type' => 'many', 'inversedBy' => 'post']],
        ];
    }

    public function testAddInheritedAssociationMapping()
    {
        $cm = new ClassMetadata('stdClass');

        $mapping = [
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID,
        ];

        $cm->addInheritedAssociationMapping($mapping);

        $expected = ['assoc' => $mapping];

        $this->assertEquals($expected, $cm->associationMappings);
    }

    public function testIdFieldsTypeMustNotBeOverridden()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->setIdentifier('id');
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('stdClass::id was declared an identifier and must stay this way.');
        $cm->mapField([
            'fieldName' => 'id',
            'type' => 'string',
        ]);
    }

    public function testReferenceManySortMustNotBeUsedWithNonSetCollectionStrategy()
    {
        $cm = new ClassMetadata('stdClass');
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'ReferenceMany\'s sort can not be used with addToSet and pushAll strategies, ' .
            'pushAll used in stdClass::ref'
        );
        $cm->mapField([
            'fieldName' => 'ref',
            'reference' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
            'type' => 'many',
            'sort' => ['foo' => 1],
        ]);
    }

    public function testSetShardKeyForClassWithoutInheritance()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->setShardKey(['id' => 'asc']);

        $shardKey = $cm->getShardKey();

        $this->assertEquals(['id' => 1], $shardKey['keys']);
    }

    public function testSetShardKeyForClassWithSingleCollectionInheritance()
    {
        $cm                  = new ClassMetadata('stdClass');
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION;
        $cm->setShardKey(['id' => 'asc']);

        $shardKey = $cm->getShardKey();

        $this->assertEquals(['id' => 1], $shardKey['keys']);
    }

    public function testSetShardKeyForClassWithSingleCollectionInheritanceWhichAlreadyHasIt()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->setShardKey(['id' => 'asc']);
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION;

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Shard key overriding in subclass is forbidden for single collection inheritance');
        $cm->setShardKey(['foo' => 'asc']);
    }

    public function testSetShardKeyForClassWithCollPerClassInheritance()
    {
        $cm                  = new ClassMetadata('stdClass');
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_COLLECTION_PER_CLASS;
        $cm->setShardKey(['id' => 'asc']);

        $shardKey = $cm->getShardKey();

        $this->assertEquals(['id' => 1], $shardKey['keys']);
    }

    public function testIsNotShardedIfThereIsNoShardKey()
    {
        $cm = new ClassMetadata('stdClass');

        $this->assertFalse($cm->isSharded());
    }

    public function testIsShardedIfThereIsAShardKey()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->setShardKey(['id' => 'asc']);

        $this->assertTrue($cm->isSharded());
    }

    public function testEmbeddedDocumentCantHaveShardKey()
    {
        $cm                     = new ClassMetadata('stdClass');
        $cm->isEmbeddedDocument = true;
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Embedded document can\'t have shard key: stdClass');
        $cm->setShardKey(['id' => 'asc']);
    }

    public function testNoIncrementFieldsAllowedInShardKey()
    {
        $object = new class {
            public $inc;
        };

        $cm = new ClassMetadata(get_class($object));
        $cm->mapField([
            'fieldName' => 'inc',
            'type' => 'int',
            'strategy' => ClassMetadata::STORAGE_STRATEGY_INCREMENT,
        ]);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Only fields using the SET strategy can be used in the shard key');
        $cm->setShardKey(['inc' => 1]);
    }

    public function testNoCollectionsInShardKey()
    {
        $object = new class {
            public $collection;
        };

        $cm = new ClassMetadata(get_class($object));
        $cm->mapField([
            'fieldName' => 'collection',
            'type' => 'collection',
        ]);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No multikey indexes are allowed in the shard key');
        $cm->setShardKey(['collection' => 1]);
    }

    public function testNoEmbedManyInShardKey()
    {
        $object = new class {
            public $embedMany;
        };

        $cm = new ClassMetadata(get_class($object));
        $cm->mapManyEmbedded(['fieldName' => 'embedMany']);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No multikey indexes are allowed in the shard key');
        $cm->setShardKey(['embedMany' => 1]);
    }

    public function testNoReferenceManyInShardKey()
    {
        $object = new class {
            public $referenceMany;
        };

        $cm = new ClassMetadata(get_class($object));
        $cm->mapManyEmbedded(['fieldName' => 'referenceMany']);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No multikey indexes are allowed in the shard key');
        $cm->setShardKey(['referenceMany' => 1]);
    }

    public function testArbitraryFieldInGridFSFileThrowsException(): void
    {
        $object = new class {
            public $contentType;
        };

        $cm         = new ClassMetadata(get_class($object));
        $cm->isFile = true;

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches("#^Field 'contentType' in class '.+' is not a valid field for GridFS documents. You should move it to an embedded metadata document.$#");

        $cm->mapField(['type' => 'string', 'fieldName' => 'contentType']);
    }

    public function testDefaultValueForValidator()
    {
        $cm = new ClassMetadata('stdClass');
        $this->assertNull($cm->getValidator());
    }

    public function testDefaultValueForValidationAction()
    {
        $cm = new ClassMetadata('stdClass');
        $this->assertEquals(ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR, $cm->getValidationAction());
    }

    public function testDefaultValueForValidationLevel()
    {
        $cm = new ClassMetadata('stdClass');
        $this->assertEquals(ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT, $cm->getValidationLevel());
    }
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

    /** @ODM\EmbedOne(targetDocument=Documents\Address::class) */
    public $address;

    /** @ODM\EmbedOne(targetDocument=Documents\Address::class) */
    public $addresses;
}
