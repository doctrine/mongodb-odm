<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use DateTime;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tests\ClassMetadataTestUtil;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;
use Doctrine\Persistence\Reflection\EnumReflectionProperty;
use DoctrineGlobal_Article;
use DoctrineGlobal_User;
use Documents\Account;
use Documents\Address;
use Documents\Album;
use Documents\Bars\Bar;
use Documents\Card;
use Documents\CmsGroup;
use Documents\CmsUser;
use Documents\CustomCollection;
use Documents\CustomRepository\Repository;
use Documents\SpecialUser;
use Documents\Suit;
use Documents\SuitInt;
use Documents\SuitNonBacked;
use Documents\User;
use Documents\UserName;
use Documents\UserRepository;
use Documents\UserTyped;
use Generator;
use InvalidArgumentException;
use MongoDB\BSON\Document;
use PHPUnit\Framework\Attributes\DataProvider;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionClass;
use ReflectionException;
use stdClass;

use function array_merge;
use function serialize;
use function unserialize;

class ClassMetadataTest extends BaseTestCase
{
    public function testClassMetadataInstanceSerialization(): void
    {
        $cm = new ClassMetadata(CmsUser::class);

        // Test initial state
        self::assertEmpty($cm->getReflectionProperties());
        self::assertInstanceOf(ReflectionClass::class, $cm->reflClass);
        self::assertEquals(CmsUser::class, $cm->name);
        self::assertEquals(CmsUser::class, $cm->rootDocumentName);
        self::assertEquals([], $cm->subClasses);
        self::assertEquals([], $cm->parentClasses);
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $cm->inheritanceType);

        // Customize state
        $cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION);
        $cm->setSubclasses([User::class, UserName::class]);
        $cm->setParentClasses([stdClass::class]);
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
        $validatorJson = '{ "$and": [ { "email": { "$regularExpression" : { "pattern": "@mongodb\\\\.com$", "options": "" } } } ] }';
        $cm->setValidator(Document::fromJSON($validatorJson)->toPHP());
        $cm->setValidationAction(ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN);
        $cm->setValidationLevel(ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF);
        self::assertIsArray($cm->getFieldMapping('phonenumbers'));
        self::assertCount(1, $cm->fieldMappings);
        self::assertCount(1, $cm->associationMappings);

        $serialized = serialize($cm);
        $cm         = unserialize($serialized);

        // Check state
        self::assertGreaterThan(0, $cm->getReflectionProperties());
        self::assertInstanceOf(ReflectionClass::class, $cm->reflClass);
        self::assertEquals(CmsUser::class, $cm->name);
        self::assertEquals(stdClass::class, $cm->rootDocumentName);
        self::assertEquals([User::class, UserName::class], $cm->subClasses);
        self::assertEquals([stdClass::class], $cm->parentClasses);
        self::assertEquals(UserRepository::class, $cm->customRepositoryClassName);
        self::assertEquals('disc', $cm->discriminatorField);
        self::assertIsArray($cm->getFieldMapping('phonenumbers'));
        self::assertCount(1, $cm->fieldMappings);
        self::assertCount(1, $cm->associationMappings);
        self::assertEquals(['keys' => ['_id' => 1], 'options' => []], $cm->getShardKey());
        $mapping = $cm->getFieldMapping('phonenumbers');
        self::assertEquals(Bar::class, $mapping['targetDocument']);
        self::assertTrue($cm->getCollectionCapped());
        self::assertEquals(1000, $cm->getCollectionMax());
        self::assertEquals(500, $cm->getCollectionSize());
        self::assertEquals(true, $cm->isLockable);
        self::assertEquals('lock', $cm->lockField);
        self::assertEquals(true, $cm->isVersioned);
        self::assertEquals('version', $cm->versionField);
        self::assertEquals(Document::fromJSON($validatorJson)->toPHP(), $cm->getValidator());
        self::assertEquals(ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN, $cm->getValidationAction());
        self::assertEquals(ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF, $cm->getValidationLevel());
    }

    public function testOwningSideAndInverseSide(): void
    {
        $cm = new ClassMetadata(User::class);
        $cm->mapOneReference(['fieldName' => 'account', 'targetDocument' => Account::class, 'inversedBy' => 'user']);
        self::assertTrue($cm->fieldMappings['account']['isOwningSide']);

        $cm = new ClassMetadata(Account::class);
        $cm->mapOneReference(['fieldName' => 'user', 'targetDocument' => Account::class, 'mappedBy' => 'account']);
        self::assertTrue($cm->fieldMappings['user']['isInverseSide']);
    }

    public function testFieldIsNullable(): void
    {
        $cm = new ClassMetadata(CmsUser::class);

        // Explicit Nullable
        $cm->mapField(['fieldName' => 'status', 'nullable' => true, 'type' => 'string']);
        self::assertTrue($cm->isNullable('status'));

        // Explicit Not Nullable
        $cm->mapField(['fieldName' => 'username', 'nullable' => false, 'type' => 'string']);
        self::assertFalse($cm->isNullable('username'));

        // Implicit Not Nullable
        $cm->mapField(['fieldName' => 'name', 'type' => 'string']);
        self::assertFalse($cm->isNullable('name'), 'By default a field should not be nullable.');
    }

    public function testFieldTypeFromReflection(): void
    {
        $cm = new ClassMetadata(UserTyped::class);

        // String
        $cm->mapField(['fieldName' => 'username']);
        self::assertEquals(Type::STRING, $cm->getTypeOfField('username'));

        // DateTime object
        $cm->mapField(['fieldName' => 'dateTime']);
        self::assertEquals(Type::DATE, $cm->getTypeOfField('dateTime'));

        // DateTimeImmutable object
        $cm->mapField(['fieldName' => 'dateTimeImmutable']);
        self::assertEquals(Type::DATE_IMMUTABLE, $cm->getTypeOfField('dateTimeImmutable'));

        // array as hash
        $cm->mapField(['fieldName' => 'array']);
        self::assertEquals(Type::HASH, $cm->getTypeOfField('array'));

        // bool
        $cm->mapField(['fieldName' => 'boolean']);
        self::assertEquals(Type::BOOL, $cm->getTypeOfField('boolean'));

        // float
        $cm->mapField(['fieldName' => 'float']);
        self::assertEquals(Type::FLOAT, $cm->getTypeOfField('float'));

        // int
        $cm->mapField(['fieldName' => 'int']);
        self::assertEquals(Type::INT, $cm->getTypeOfField('int'));

        $cm->mapManyEmbedded(['fieldName' => 'embedMany']);
        self::assertEquals(CustomCollection::class, $cm->getAssociationCollectionClass('embedMany'));

        $cm->mapManyReference(['fieldName' => 'referenceMany']);
        self::assertEquals(CustomCollection::class, $cm->getAssociationCollectionClass('referenceMany'));
    }

    public function testEnumTypeFromReflection(): void
    {
        $cm = new ClassMetadata(Card::class);

        $cm->mapField(['fieldName' => 'suit']);
        self::assertEquals(Type::STRING, $cm->getTypeOfField('suit'));
        self::assertSame(Suit::class, $cm->fieldMappings['suit']['enumType']);
        self::assertFalse($cm->isNullable('suit'));

        $cm->mapField(['fieldName' => 'suitInt']);
        self::assertEquals(Type::INT, $cm->getTypeOfField('suitInt'));
        self::assertSame(SuitInt::class, $cm->fieldMappings['suitInt']['enumType']);
        self::assertFalse($cm->isNullable('suitInt'));

        $cm->mapField(['fieldName' => 'nullableSuit']);
        self::assertEquals(Type::STRING, $cm->getTypeOfField('nullableSuit'));
        self::assertSame(Suit::class, $cm->fieldMappings['nullableSuit']['enumType']);
        self::assertFalse($cm->isNullable('nullableSuit'));
    }

    public function testEnumReflectionPropertySerialization(): void
    {
        $cm = new ClassMetadata(Card::class);

        $cm->mapField(['fieldName' => 'suit']);
        self::assertInstanceOf(EnumReflectionProperty::class, $cm->reflFields['suit']);

        $serialized = serialize($cm);
        $cm         = unserialize($serialized);

        self::assertInstanceOf(EnumReflectionProperty::class, $cm->reflFields['suit']);
    }

    public function testEnumTypeFromReflectionMustBeBacked(): void
    {
        $cm = new ClassMetadata(Card::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Attempting to map a non-backed enum Documents\SuitNonBacked: Documents\Card::suitNonBacked',
        );
        $cm->mapField(['fieldName' => 'suitNonBacked']);
    }

    public function testEnumTypeMustPointToAnEnum(): void
    {
        $object = new class {
            /** @var Card|null */
            public $enum;
        };

        $cm = new ClassMetadata($object::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Attempting to map a non-enum type Documents\Card as an enum: ',
        );
        // @phpstan-ignore-next-line
        $cm->mapField([
            'fieldName' => 'enum',
            'enumType' => Card::class,
        ]);
    }

    public function testEnumTypeMustPointToABackedEnum(): void
    {
        $object = new class {
            /** @var SuitNonBacked|null */
            public $enum;
        };

        $cm = new ClassMetadata($object::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Attempting to map a non-backed enum Documents\SuitNonBacked: ',
        );
        // @phpstan-ignore-next-line
        $cm->mapField([
            'fieldName' => 'enum',
            'enumType' => SuitNonBacked::class,
        ]);
    }

    /** @group DDC-115 */
    public function testMapAssocationInGlobalNamespace(): void
    {
        require_once __DIR__ . '/Documents/GlobalNamespaceDocument.php';

        $cm = new ClassMetadata(DoctrineGlobal_Article::class);
        $cm->mapManyEmbedded([
            'fieldName' => 'author',
            'targetDocument' => DoctrineGlobal_User::class,
        ]);

        self::assertEquals(DoctrineGlobal_User::class, $cm->fieldMappings['author']['targetDocument']);
    }

    public function testMapManyToManyJoinTableDefaults(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapManyEmbedded(
            [
                'fieldName' => 'groups',
                'targetDocument' => CmsGroup::class,
            ],
        );

        $assoc = $cm->fieldMappings['groups'];
        self::assertIsArray($assoc);
    }

    public function testGetAssociationTargetClassWithoutTargetDocument(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapManyEmbedded(
            [
                'fieldName' => 'groups',
                'targetDocument' => null,
            ],
        );

        self::assertNull($cm->getAssociationTargetClass('groups'));
    }

    /** @group DDC-115 */
    public function testSetDiscriminatorMapInGlobalNamespace(): void
    {
        require_once __DIR__ . '/Documents/GlobalNamespaceDocument.php';

        $cm = new ClassMetadata(DoctrineGlobal_User::class);
        $cm->setDiscriminatorMap(['descr' => DoctrineGlobal_Article::class, 'foo' => DoctrineGlobal_User::class]);

        self::assertEquals(DoctrineGlobal_Article::class, $cm->discriminatorMap['descr']);
        self::assertEquals(DoctrineGlobal_User::class, $cm->discriminatorMap['foo']);
    }

    /** @group DDC-115 */
    public function testSetSubClassesInGlobalNamespace(): void
    {
        require_once __DIR__ . '/Documents/GlobalNamespaceDocument.php';

        $cm = new ClassMetadata(DoctrineGlobal_User::class);
        $cm->setSubclasses([DoctrineGlobal_Article::class]);

        self::assertEquals(DoctrineGlobal_Article::class, $cm->subClasses[0]);
    }

    public function testDuplicateFieldMapping(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $a1 = ['reference' => true, 'type' => 'many', 'fieldName' => 'name', 'targetDocument' => stdClass::class];
        $a2 = ['type' => 'string', 'fieldName' => 'name'];

        $cm->mapField($a1);
        $cm->mapField($a2);

        self::assertEquals('string', $cm->fieldMappings['name']['type']);
    }

    public function testDuplicateColumnNameDiscriminatorColumnThrowsMappingException(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapField(['fieldName' => 'name', 'type' => Type::STRING]);

        $this->expectException(MappingException::class);
        $cm->setDiscriminatorField('name');
    }

    public function testDuplicateFieldNameDiscriminatorColumn2ThrowsMappingException(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->setDiscriminatorField('name');

        $this->expectException(MappingException::class);
        $cm->mapField(['fieldName' => 'name', 'type' => Type::STRING]);
    }

    public function testDuplicateFieldAndAssocationMapping1(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapField(['fieldName' => 'name', 'type' => Type::STRING]);
        $cm->mapOneEmbedded(['fieldName' => 'name', 'targetDocument' => CmsUser::class]);

        self::assertEquals('one', $cm->fieldMappings['name']['type']);
    }

    public function testDuplicateFieldAndAssocationMapping2(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->mapOneEmbedded(['fieldName' => 'name', 'targetDocument' => CmsUser::class]);
        $cm->mapField(['fieldName' => 'name', 'type' => 'string']);

        self::assertEquals('string', $cm->fieldMappings['name']['type']);
    }

    public function testMapNotExistingFieldThrowsException(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $this->expectException(ReflectionException::class);
        $cm->mapField(['fieldName' => 'namee', 'type' => 'string']);
    }

    /** @param ClassMetadata<CmsUser> $cm */
    #[DataProvider('dataProviderMetadataClasses')]
    public function testEmbeddedDocumentWithDiscriminator(ClassMetadata $cm): void
    {
        $cm->setDiscriminatorField('discriminator');
        $cm->setDiscriminatorValue('discriminatorValue');

        $serialized = serialize($cm);
        $cm         = unserialize($serialized);

        self::assertSame('discriminator', $cm->discriminatorField);
        self::assertSame('discriminatorValue', $cm->discriminatorValue);
    }

    public static function dataProviderMetadataClasses(): array
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

    public function testDefaultDiscriminatorField(): void
    {
        $object = new class {
            /** @var object|null */
            public $assoc;

            /** @var stdClass|null */
            public $assocWithTargetDocument;

            /** @var object|null */
            public $assocWithDiscriminatorField;
        };

        $cm = new ClassMetadata($object::class);

        $cm->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
        ]);

        $cm->mapField([
            'fieldName' => 'assocWithTargetDocument',
            'reference' => true,
            'type' => 'one',
            'targetDocument' => stdClass::class,
        ]);

        $cm->mapField([
            'fieldName' => 'assocWithDiscriminatorField',
            'reference' => true,
            'type' => 'one',
            'discriminatorField' => 'type',
        ]);

        $mapping = $cm->getFieldMapping('assoc');

        self::assertEquals(
            ClassMetadata::DEFAULT_DISCRIMINATOR_FIELD,
            $mapping['discriminatorField'],
            'Default discriminator field is set for associations without targetDocument and discriminatorField options',
        );

        $mapping = $cm->getFieldMapping('assocWithTargetDocument');

        self::assertArrayNotHasKey(
            'discriminatorField',
            $mapping,
            'Default discriminator field is not set for associations with targetDocument option',
        );

        $mapping = $cm->getFieldMapping('assocWithDiscriminatorField');

        self::assertEquals(
            'type',
            $mapping['discriminatorField'],
            'Default discriminator field is not set for associations with discriminatorField option',
        );
    }

    public function testGetFieldValue(): void
    {
        $document = new Album('ten');
        $metadata = $this->dm->getClassMetadata(Album::class);

        self::assertEquals($document->getName(), $metadata->getFieldValue($document, 'name'));
    }

    public function testGetFieldValueInitializesProxy(): void
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy    = $this->dm->getReference(Album::class, $document->getId());
        $metadata = $this->dm->getClassMetadata(Album::class);

        self::assertEquals($document->getName(), $metadata->getFieldValue($proxy, 'name'));
        self::assertInstanceOf(GhostObjectInterface::class, $proxy);
        self::assertFalse($this->uow->isUninitializedObject($proxy));
    }

    public function testGetFieldValueOfIdentifierDoesNotInitializeProxy(): void
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy    = $this->dm->getReference(Album::class, $document->getId());
        $metadata = $this->dm->getClassMetadata(Album::class);

        self::assertEquals($document->getId(), $metadata->getFieldValue($proxy, 'id'));
        self::assertInstanceOf(GhostObjectInterface::class, $proxy);
        self::assertTrue($this->uow->isUninitializedObject($proxy));
    }

    public function testSetFieldValue(): void
    {
        $document = new Album('ten');
        $metadata = $this->dm->getClassMetadata(Album::class);

        $metadata->setFieldValue($document, 'name', 'nevermind');

        self::assertEquals('nevermind', $document->getName());
    }

    public function testSetFieldValueWithProxy(): void
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference(Album::class, $document->getId());
        self::assertInstanceOf(GhostObjectInterface::class, $proxy);

        $metadata = $this->dm->getClassMetadata(Album::class);
        $metadata->setFieldValue($proxy, 'name', 'nevermind');

        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference(Album::class, $document->getId());
        self::assertInstanceOf(GhostObjectInterface::class, $proxy);
        self::assertInstanceOf(Album::class, $proxy);

        self::assertEquals('nevermind', $proxy->getName());
    }

    public function testSetCustomRepositoryClass(): void
    {
        $cm = new ClassMetadata(self::class);

        $cm->setCustomRepositoryClass(Repository::class);

        self::assertEquals(Repository::class, $cm->customRepositoryClassName);

        $cm->setCustomRepositoryClass(TestCustomRepositoryClass::class);

        self::assertEquals(TestCustomRepositoryClass::class, $cm->customRepositoryClassName);
    }

    public function testEmbeddedAssociationsAlwaysCascade(): void
    {
        $class = $this->dm->getClassMetadata(EmbeddedAssociationsCascadeTest::class);

        self::assertTrue($class->fieldMappings['address']['isCascadeRemove']);
        self::assertTrue($class->fieldMappings['address']['isCascadePersist']);
        self::assertTrue($class->fieldMappings['address']['isCascadeRefresh']);
        self::assertTrue($class->fieldMappings['address']['isCascadeMerge']);
        self::assertTrue($class->fieldMappings['address']['isCascadeDetach']);

        self::assertTrue($class->fieldMappings['addresses']['isCascadeRemove']);
        self::assertTrue($class->fieldMappings['addresses']['isCascadePersist']);
        self::assertTrue($class->fieldMappings['addresses']['isCascadeRefresh']);
        self::assertTrue($class->fieldMappings['addresses']['isCascadeMerge']);
        self::assertTrue($class->fieldMappings['addresses']['isCascadeDetach']);
    }

    public function testEmbedWithCascadeThrowsMappingException(): void
    {
        $class = new ClassMetadata(EmbedWithCascadeTest::class);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Cascade on Doctrine\ODM\MongoDB\Tests\Mapping\EmbedWithCascadeTest::address is not allowed.',
        );
        $class->mapOneEmbedded([
            'fieldName' => 'address',
            'targetDocument' => Address::class,
            'cascade' => 'all',
        ]);
    }

    public function testInvokeLifecycleCallbacksShouldRequireInstanceOfClass(): void
    {
        $class    = $this->dm->getClassMetadata(User::class);
        $document = new stdClass();

        self::assertInstanceOf(stdClass::class, $document);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected document class "Documents\User"; found: "stdClass"');
        $class->invokeLifecycleCallbacks(Events::prePersist, $document);
    }

    public function testInvokeLifecycleCallbacksAllowsInstanceOfClass(): void
    {
        $class    = $this->dm->getClassMetadata(User::class);
        $document = new SpecialUser();

        self::assertInstanceOf(SpecialUser::class, $document);

        $class->invokeLifecycleCallbacks(Events::prePersist, $document);
    }

    public function testInvokeLifecycleCallbacksShouldAllowProxyOfExactClass(): void
    {
        $document = new User();
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $class = $this->dm->getClassMetadata(User::class);
        $proxy = $this->dm->getReference(User::class, $document->getId());

        self::assertInstanceOf(User::class, $proxy);

        $class->invokeLifecycleCallbacks(Events::prePersist, $proxy);
    }

    public function testSimpleReferenceRequiresTargetDocument(): void
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

    public function testSimpleAsStringReferenceRequiresTargetDocument(): void
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

    /** @param mixed $value */
    #[DataProvider('provideRepositoryMethodCanNotBeCombinedWithSkipLimitAndSort')]
    public function testRepositoryMethodCanNotBeCombinedWithSkipLimitAndSort(string $prop, $value): void
    {
        $cm = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            '\'repositoryMethod\' used on \'assoc\' in class \'stdClass\' can not be combined with skip, ' .
            'limit or sort.',
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

    public static function provideRepositoryMethodCanNotBeCombinedWithSkipLimitAndSort(): Generator
    {
        yield ['skip', 5];
        yield ['limit', 5];
        yield ['sort', ['time' => 1]];
    }

    public function testStoreAsIdReferenceRequiresTargetDocument(): void
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

    public function testAtomicCollectionUpdateUsageInEmbeddedDocument(): void
    {
        $object = new class {
            /** @var object[] */
            public $many;
        };

        $cm                     = new ClassMetadata($object::class);
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

    public function testDefaultStorageStrategyOfEmbeddedDocumentFields(): void
    {
        $object = new class {
            /** @var object[] */
            public $many;
        };

        $cm                     = new ClassMetadata($object::class);
        $cm->isEmbeddedDocument = true;

        $mapping = $cm->mapField([
            'fieldName' => 'many',
            'type' => 'many',
        ]);

        self::assertEquals(CollectionHelper::DEFAULT_STRATEGY, $mapping['strategy']);
    }

    /** @param array<string, mixed> $config */
    #[DataProvider('provideOwningAndInversedRefsNeedTargetDocument')]
    public function testOwningAndInversedRefsNeedTargetDocument(array $config): void
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

    public static function provideOwningAndInversedRefsNeedTargetDocument(): array
    {
        return [
            [['type' => 'one', 'mappedBy' => 'post']],
            [['type' => 'one', 'inversedBy' => 'post']],
            [['type' => 'many', 'mappedBy' => 'post']],
            [['type' => 'many', 'inversedBy' => 'post']],
        ];
    }

    public function testAddInheritedAssociationMapping(): void
    {
        $cm = new ClassMetadata('stdClass');

        $mapping = ClassMetadataTestUtil::getFieldMapping([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID,
        ]);

        $cm->addInheritedAssociationMapping($mapping);

        $expected = ['assoc' => $mapping];

        self::assertEquals($expected, $cm->associationMappings);
    }

    public function testIdFieldsTypeMustNotBeOverridden(): void
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

    public function testReferenceManySortMustNotBeUsedWithNonSetCollectionStrategy(): void
    {
        $cm = new ClassMetadata('stdClass');
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'ReferenceMany\'s sort can not be used with addToSet and pushAll strategies, ' .
            'pushAll used in stdClass::ref',
        );
        $cm->mapField([
            'fieldName' => 'ref',
            'reference' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
            'type' => 'many',
            'sort' => ['foo' => 1],
        ]);
    }

    public function testSetShardKeyForClassWithoutInheritance(): void
    {
        $cm = new ClassMetadata('stdClass');
        $cm->setShardKey(['id' => 'asc']);

        $shardKey = $cm->getShardKey();

        self::assertEquals(['id' => 1], $shardKey['keys']);
    }

    public function testSetShardKeyForClassWithSingleCollectionInheritance(): void
    {
        $cm                  = new ClassMetadata('stdClass');
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION;
        $cm->setShardKey(['id' => 'asc']);

        $shardKey = $cm->getShardKey();

        self::assertEquals(['id' => 1], $shardKey['keys']);
    }

    public function testSetShardKeyForClassWithSingleCollectionInheritanceWhichAlreadyHasIt(): void
    {
        $cm = new ClassMetadata('stdClass');
        $cm->setShardKey(['id' => 'asc']);
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION;

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Shard key overriding in subclass is forbidden for single collection inheritance');
        $cm->setShardKey(['foo' => 'asc']);
    }

    public function testSetShardKeyForClassWithCollPerClassInheritance(): void
    {
        $cm                  = new ClassMetadata('stdClass');
        $cm->inheritanceType = ClassMetadata::INHERITANCE_TYPE_COLLECTION_PER_CLASS;
        $cm->setShardKey(['id' => 'asc']);

        $shardKey = $cm->getShardKey();

        self::assertEquals(['id' => 1], $shardKey['keys']);
    }

    public function testIsNotShardedIfThereIsNoShardKey(): void
    {
        $cm = new ClassMetadata('stdClass');

        self::assertFalse($cm->isSharded());
    }

    public function testIsShardedIfThereIsAShardKey(): void
    {
        $cm = new ClassMetadata('stdClass');
        $cm->setShardKey(['id' => 'asc']);

        self::assertTrue($cm->isSharded());
    }

    public function testEmbeddedDocumentCantHaveShardKey(): void
    {
        $cm                     = new ClassMetadata('stdClass');
        $cm->isEmbeddedDocument = true;
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Embedded document can\'t have shard key: stdClass');
        $cm->setShardKey(['id' => 'asc']);
    }

    public function testNoIncrementFieldsAllowedInShardKey(): void
    {
        $object = new class {
            /** @var int|null */
            public $inc;
        };

        $cm = new ClassMetadata($object::class);
        $cm->mapField([
            'fieldName' => 'inc',
            'type' => 'int',
            'strategy' => ClassMetadata::STORAGE_STRATEGY_INCREMENT,
        ]);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Only fields using the SET strategy can be used in the shard key');
        $cm->setShardKey(['inc' => 1]);
    }

    public function testNoCollectionsInShardKey(): void
    {
        $object = new class {
            /** @var object[] */
            public $collection;
        };

        $cm = new ClassMetadata($object::class);
        $cm->mapField([
            'fieldName' => 'collection',
            'type' => 'collection',
        ]);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No multikey indexes are allowed in the shard key');
        $cm->setShardKey(['collection' => 1]);
    }

    public function testNoEmbedManyInShardKey(): void
    {
        $object = new class {
            /** @var object[] */
            public $embedMany;
        };

        $cm = new ClassMetadata($object::class);
        $cm->mapManyEmbedded(['fieldName' => 'embedMany']);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No multikey indexes are allowed in the shard key');
        $cm->setShardKey(['embedMany' => 1]);
    }

    public function testNoReferenceManyInShardKey(): void
    {
        $object = new class {
            /** @var object[] */
            public $referenceMany;
        };

        $cm = new ClassMetadata($object::class);
        $cm->mapManyEmbedded(['fieldName' => 'referenceMany']);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No multikey indexes are allowed in the shard key');
        $cm->setShardKey(['referenceMany' => 1]);
    }

    public function testArbitraryFieldInGridFSFileThrowsException(): void
    {
        $object = new class {
            /** @var string|null */
            public $contentType;
        };

        $cm         = new ClassMetadata($object::class);
        $cm->isFile = true;

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches("#^Field 'contentType' in class '.+' is not a valid field for GridFS documents. You should move it to an embedded metadata document.$#");

        $cm->mapField(['type' => 'string', 'fieldName' => 'contentType']);
    }

    public function testDefaultValueForValidator(): void
    {
        $cm = new ClassMetadata('stdClass');
        self::assertNull($cm->getValidator());
    }

    public function testDefaultValueForValidationAction(): void
    {
        $cm = new ClassMetadata('stdClass');
        self::assertEquals(ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR, $cm->getValidationAction());
    }

    public function testDefaultValueForValidationLevel(): void
    {
        $cm = new ClassMetadata('stdClass');
        self::assertEquals(ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT, $cm->getValidationLevel());
    }

    public function testEmptySearchIndexDefinition(): void
    {
        $cm = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('stdClass search index "default" must be dynamic or specify a field mapping');
        $cm->addSearchIndex(['mappings' => []]);
    }

    public function testTimeSeriesMappingOnlyWithTimeField(): void
    {
        $metadata = $this->dm->getClassMetadata(TimeSeriesTestDocument::class);
        $metadata->markAsTimeSeries(new ODM\TimeSeries('time'));

        self::assertNotNull($metadata->timeSeriesOptions);
        self::assertSame('time', $metadata->timeSeriesOptions->timeField);
    }

    public function testTimeSeriesMappingWithMissingTimeField(): void
    {
        $metadata = $this->dm->getClassMetadata(TimeSeriesTestDocument::class);

        self::expectExceptionObject(MappingException::timeSeriesFieldNotFound(TimeSeriesTestDocument::class, 'foo', 'time'));
        $metadata->markAsTimeSeries(new ODM\TimeSeries('foo'));
    }

    public function testTimeSeriesMappingWithMetadataField(): void
    {
        $metadata = $this->dm->getClassMetadata(TimeSeriesTestDocument::class);
        $metadata->markAsTimeSeries(new ODM\TimeSeries('time', 'metadata'));

        self::assertNotNull($metadata->timeSeriesOptions);
        self::assertSame('metadata', $metadata->timeSeriesOptions->metaField);
    }

    public function testTimeSeriesMappingWithMissingMetadataField(): void
    {
        $metadata = $this->dm->getClassMetadata(TimeSeriesTestDocument::class);

        self::expectExceptionObject(MappingException::timeSeriesFieldNotFound(TimeSeriesTestDocument::class, 'foo', 'metadata'));
        $metadata->markAsTimeSeries(new ODM\TimeSeries('time', 'foo'));
    }

    public function testTimeSeriesMappingWithExpireAfterSeconds(): void
    {
        $metadata = $this->dm->getClassMetadata(TimeSeriesTestDocument::class);
        $metadata->markAsTimeSeries(new ODM\TimeSeries('time', expireAfterSeconds: 10));

        self::assertSame(10, $metadata->timeSeriesOptions->expireAfterSeconds);
    }

    public function testTimeSeriesMappingWithGranularityAndBucketMaxSpanSeconds(): void
    {
        $metadata = $this->dm->getClassMetadata(TimeSeriesTestDocument::class);
        $metadata->markAsTimeSeries(new ODM\TimeSeries('time', granularity: Granularity::Hours, bucketMaxSpanSeconds: 15, bucketRoundingSeconds: 20));

        /*
         * We don't throw for invalid settings here, including:
         * - bucketMaxSpanSeconds not being equal to bucketRoundingSeconds
         * - granularity and bucket settings applied together
         */
        self::assertSame(Granularity::Hours, $metadata->timeSeriesOptions->granularity);
        self::assertSame(15, $metadata->timeSeriesOptions->bucketMaxSpanSeconds);
        self::assertSame(20, $metadata->timeSeriesOptions->bucketRoundingSeconds);
    }
}

/** @template-extends DocumentRepository<self> */
class TestCustomRepositoryClass extends DocumentRepository
{
}

class EmbedWithCascadeTest
{
    /** @var Address|null */
    public $address;
}

#[ODM\Document]
class EmbeddedAssociationsCascadeTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var Address|null */
    #[ODM\EmbedOne(targetDocument: Address::class)]
    public $address;

    /** @var Address|null */
    #[ODM\EmbedOne(targetDocument: Address::class)]
    public $addresses;
}

#[ODM\Document]
class TimeSeriesTestDocument
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field]
    public DateTime $time;

    #[ODM\Field]
    public string $metadata;
}
