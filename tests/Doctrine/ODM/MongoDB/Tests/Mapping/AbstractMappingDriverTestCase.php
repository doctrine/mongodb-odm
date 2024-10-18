<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;
use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\ViewRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Reflection\EnumReflectionProperty;
use Documents\Card;
use Documents\CustomCollection;
use Documents\Suit;
use Documents\UserTyped;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

use function key;
use function sprintf;
use function strcmp;
use function usort;

abstract class AbstractMappingDriverTestCase extends BaseTestCase
{
    abstract protected static function loadDriver(): MappingDriver;

    protected static function createMetadataDriverImpl(): MappingDriver
    {
        return static::loadDriver();
    }

    /** @return ClassMetadata<AbstractMappingDriverUser> */
    #[DoesNotPerformAssertions]
    public function testLoadMapping(): ClassMetadata
    {
        return $this->dm->getClassMetadata(AbstractMappingDriverUser::class);
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testLoadMapping')]
    public function testDocumentCollectionNameAndInheritance(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('cms_users', $class->getCollection());
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $class->inheritanceType);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testLoadMapping')]
    public function testDocumentMarkedAsReadOnly(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue($class->isReadOnly);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testDocumentCollectionNameAndInheritance')]
    public function testDocumentLevelReadPreference(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('primaryPreferred', $class->readPreference);
        self::assertEquals([
            ['dc' => 'east'],
            ['dc' => 'west'],
            [],
        ], $class->readPreferenceTags);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testDocumentCollectionNameAndInheritance')]
    public function testDocumentLevelWriteConcern(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals(1, $class->getWriteConcern());

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testDocumentLevelWriteConcern')]
    public function testFieldMappings(ClassMetadata $class): ClassMetadata
    {
        self::assertCount(14, $class->fieldMappings);
        self::assertTrue(isset($class->fieldMappings['identifier']));
        self::assertTrue(isset($class->fieldMappings['version']));
        self::assertTrue(isset($class->fieldMappings['lock']));
        self::assertTrue(isset($class->fieldMappings['name']));
        self::assertTrue(isset($class->fieldMappings['email']));
        self::assertTrue(isset($class->fieldMappings['roles']));

        return $class;
    }

    /** @param ClassMetadata<AbstractMappingDriverUser> $class */
    #[Depends('testDocumentCollectionNameAndInheritance')]
    public function testAssociationMappings(ClassMetadata $class): void
    {
        self::assertCount(6, $class->associationMappings);
        self::assertTrue(isset($class->associationMappings['address']));
        self::assertTrue(isset($class->associationMappings['phonenumbers']));
        self::assertTrue(isset($class->associationMappings['groups']));
        self::assertTrue(isset($class->associationMappings['morePhoneNumbers']));
        self::assertTrue(isset($class->associationMappings['embeddedPhonenumber']));
        self::assertTrue(isset($class->associationMappings['otherPhonenumbers']));
    }

    /** @param ClassMetadata<AbstractMappingDriverUser> $class */
    #[Depends('testDocumentCollectionNameAndInheritance')]
    public function testGetAssociationTargetClass(ClassMetadata $class): void
    {
        self::assertEquals(Address::class, $class->getAssociationTargetClass('address'));
        self::assertEquals(Group::class, $class->getAssociationTargetClass('groups'));
        self::assertNull($class->getAssociationTargetClass('phonenumbers'));
        self::assertEquals(Phonenumber::class, $class->getAssociationTargetClass('morePhoneNumbers'));
        self::assertEquals(Phonenumber::class, $class->getAssociationTargetClass('embeddedPhonenumber'));
        self::assertNull($class->getAssociationTargetClass('otherPhonenumbers'));
    }

    /** @param ClassMetadata<AbstractMappingDriverUser> $class */
    #[Depends('testDocumentCollectionNameAndInheritance')]
    public function testGetAssociationTargetClassThrowsExceptionWhenEmpty(ClassMetadata $class): void
    {
        $this->expectException(InvalidArgumentException::class);
        $class->getAssociationTargetClass('invalid_association');
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testDocumentCollectionNameAndInheritance')]
    public function testStringFieldMappings(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('string', $class->fieldMappings['name']['type']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testFieldMappings')]
    public function testIdentifier(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('identifier', $class->identifier);

        return $class;
    }

    public function testFieldTypeFromReflection(): void
    {
        $class = $this->dm->getClassMetadata(UserTyped::class);

        self::assertSame(Type::ID, $class->getTypeOfField('id'));
        self::assertSame(Type::STRING, $class->getTypeOfField('username'));
        self::assertSame(Type::DATE, $class->getTypeOfField('dateTime'));
        self::assertSame(Type::DATE_IMMUTABLE, $class->getTypeOfField('dateTimeImmutable'));
        self::assertSame(Type::HASH, $class->getTypeOfField('array'));
        self::assertSame(Type::BOOL, $class->getTypeOfField('boolean'));
        self::assertSame(Type::FLOAT, $class->getTypeOfField('float'));

        self::assertSame(CustomCollection::class, $class->getAssociationCollectionClass('embedMany'));
        self::assertSame(CustomCollection::class, $class->getAssociationCollectionClass('referenceMany'));
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testFieldMappings')]
    public function testVersionFieldMappings(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('int', $class->fieldMappings['version']['type']);
        self::assertNotEmpty($class->fieldMappings['version']['version']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testFieldMappings')]
    public function testLockFieldMappings(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('int', $class->fieldMappings['lock']['type']);
        self::assertNotEmpty($class->fieldMappings['lock']['lock']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testIdentifier')]
    public function testAssocations(ClassMetadata $class): ClassMetadata
    {
        self::assertCount(14, $class->fieldMappings);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testAssocations')]
    public function testOwningOneToOneAssociation(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue(isset($class->fieldMappings['address']));
        self::assertIsArray($class->fieldMappings['address']);
        // Check cascading
        self::assertTrue($class->fieldMappings['address']['isCascadeRemove']);
        self::assertFalse($class->fieldMappings['address']['isCascadePersist']);
        self::assertFalse($class->fieldMappings['address']['isCascadeRefresh']);
        self::assertFalse($class->fieldMappings['address']['isCascadeDetach']);
        self::assertFalse($class->fieldMappings['address']['isCascadeMerge']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testOwningOneToOneAssociation')]
    public function testLifecycleCallbacks(ClassMetadata $class): ClassMetadata
    {
        $expectedLifecycleCallbacks = [
            'prePersist' => ['doStuffOnPrePersist', 'doOtherStuffOnPrePersistToo'],
            'postPersist' => ['doStuffOnPostPersist'],
        ];

        self::assertEquals($expectedLifecycleCallbacks, $class->lifecycleCallbacks);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testLifecycleCallbacks')]
    public function testCustomFieldName(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('name', $class->fieldMappings['name']['fieldName']);
        self::assertEquals('username', $class->fieldMappings['name']['name']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testCustomFieldName')]
    public function testCustomReferenceFieldName(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('morePhoneNumbers', $class->fieldMappings['morePhoneNumbers']['fieldName']);
        self::assertEquals('more_phone_numbers', $class->fieldMappings['morePhoneNumbers']['name']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testCustomReferenceFieldName')]
    public function testCustomEmbedFieldName(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('embeddedPhonenumber', $class->fieldMappings['embeddedPhonenumber']['fieldName']);
        self::assertEquals('embedded_phone_number', $class->fieldMappings['embeddedPhonenumber']['name']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testCustomEmbedFieldName')]
    public function testDiscriminator(ClassMetadata $class): ClassMetadata
    {
        self::assertEquals('discr', $class->discriminatorField);
        self::assertEquals(['default' => AbstractMappingDriverUser::class], $class->discriminatorMap);
        self::assertEquals('default', $class->defaultDiscriminatorValue);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testDiscriminator')]
    public function testEmbedDiscriminator(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue(isset($class->fieldMappings['otherPhonenumbers']['discriminatorField']));
        self::assertTrue(isset($class->fieldMappings['otherPhonenumbers']['discriminatorMap']));
        self::assertTrue(isset($class->fieldMappings['otherPhonenumbers']['defaultDiscriminatorValue']));
        self::assertEquals('discr', $class->fieldMappings['otherPhonenumbers']['discriminatorField']);
        self::assertEquals([
            'home' => HomePhonenumber::class,
            'work' => WorkPhonenumber::class,
        ], $class->fieldMappings['otherPhonenumbers']['discriminatorMap']);
        self::assertEquals('home', $class->fieldMappings['otherPhonenumbers']['defaultDiscriminatorValue']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testEmbedDiscriminator')]
    public function testReferenceDiscriminator(ClassMetadata $class): ClassMetadata
    {
        self::assertTrue(isset($class->fieldMappings['phonenumbers']['discriminatorField']));
        self::assertTrue(isset($class->fieldMappings['phonenumbers']['discriminatorMap']));
        self::assertTrue(isset($class->fieldMappings['phonenumbers']['defaultDiscriminatorValue']));
        self::assertEquals('discr', $class->fieldMappings['phonenumbers']['discriminatorField']);
        self::assertEquals([
            'home' => HomePhonenumber::class,
            'work' => WorkPhonenumber::class,
        ], $class->fieldMappings['phonenumbers']['discriminatorMap']);
        self::assertEquals('home', $class->fieldMappings['phonenumbers']['defaultDiscriminatorValue']);

        return $class;
    }

    /**
     * @param ClassMetadata<AbstractMappingDriverUser> $class
     *
     * @return ClassMetadata<AbstractMappingDriverUser>
     */
    #[Depends('testCustomFieldName')]
    public function testIndexes(ClassMetadata $class): ClassMetadata
    {
        $indexes = $class->indexes;

        /* Sort indexes by their first fieldname. This is necessary since the
         * index registration order may differ among drivers.
         */
        self::assertTrue(usort($indexes, static fn (array $a, array $b) => strcmp(key($a['keys']), key($b['keys']))));

        self::assertTrue(isset($indexes[0]['keys']['createdAt']));
        self::assertEquals(1, $indexes[0]['keys']['createdAt']);
        self::assertNotEmpty($indexes[0]['options']);
        self::assertTrue(isset($indexes[0]['options']['expireAfterSeconds']));
        self::assertSame(3600, $indexes[0]['options']['expireAfterSeconds']);

        self::assertTrue(isset($indexes[1]['keys']['email']));
        self::assertEquals(-1, $indexes[1]['keys']['email']);
        self::assertNotEmpty($indexes[1]['options']);
        self::assertTrue(isset($indexes[1]['options']['unique']));
        self::assertEquals(true, $indexes[1]['options']['unique']);

        self::assertTrue(isset($indexes[2]['keys']['lock']));
        self::assertEquals(1, $indexes[2]['keys']['lock']);
        self::assertNotEmpty($indexes[2]['options']);
        self::assertTrue(isset($indexes[2]['options']['partialFilterExpression']));
        self::assertSame(['version' => ['$gt' => 1], 'discr' => ['$eq' => 'default']], $indexes[2]['options']['partialFilterExpression']);

        self::assertTrue(isset($indexes[3]['keys']['mysqlProfileId']));
        self::assertEquals(-1, $indexes[3]['keys']['mysqlProfileId']);
        self::assertNotEmpty($indexes[3]['options']);
        self::assertTrue(isset($indexes[3]['options']['unique']));
        self::assertEquals(true, $indexes[3]['options']['unique']);

        self::assertTrue(isset($indexes[4]['keys']['username']));
        self::assertEquals(-1, $indexes[4]['keys']['username']);
        self::assertTrue(isset($indexes[4]['options']['unique']));
        self::assertEquals(true, $indexes[4]['options']['unique']);

        return $class;
    }

    /** @param ClassMetadata<AbstractMappingDriverUser> $class */
    #[Depends('testLoadMapping')]
    public function testSearchIndexes(ClassMetadata $class): void
    {
        $expectedIndexes = [
            [
                'name' => 'default',
                'definition' => [
                    'mappings' => ['dynamic' => true],
                    'analyzer' => 'lucene.standard',
                    'searchAnalyzer' => 'lucene.standard',
                    'storedSource' => true,
                ],
            ],
            [
                'name' => 'usernameAndPhoneNumbers',
                'definition' => [
                    'mappings' => [
                        'fields' => [
                            'username' => [
                                [
                                    'type' => 'string',
                                    'multi' => [
                                        'english' => ['type' => 'string', 'analyzer' => 'lucene.english'],
                                        'french' => ['type' => 'string', 'analyzer' => 'lucene.french'],
                                    ],
                                ],
                                ['type' => 'autocomplete'],
                            ],
                            'embedded_phone_number' => [
                                'type' => 'embeddedDocuments',
                                'dynamic' => true,
                            ],
                        ],
                    ],
                    'storedSource' => [
                        'include' => ['username'],
                    ],
                    'synonyms' => [
                        [
                            'name' => 'mySynonyms',
                            'analyzer' => 'lucene.english',
                            'source' => ['collection' => 'synonyms'],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedIndexes, $class->getSearchIndexes());
    }

    /** @param ClassMetadata<AbstractMappingDriverUser> $class */
    #[Depends('testIndexes')]
    public function testShardKey(ClassMetadata $class): void
    {
        $shardKey = $class->getShardKey();

        self::assertTrue(isset($shardKey['keys']['name']), 'Shard key is not mapped');
        self::assertEquals(1, $shardKey['keys']['name'], 'Wrong value for shard key');

        self::assertTrue(isset($shardKey['options']['unique']), 'Shard key option is not mapped');
        self::assertTrue($shardKey['options']['unique'], 'Shard key option has wrong value');
        self::assertTrue(isset($shardKey['options']['numInitialChunks']), 'Shard key option is not mapped');
        self::assertEquals(4096, $shardKey['options']['numInitialChunks'], 'Shard key option has wrong value');
    }

    /** @param ClassMetadata<AbstractMappingDriverUser> $class */
    #[Depends('testLoadMapping')]
    public function testStoreEmptyArray(ClassMetadata $class): void
    {
        $referenceMapping = $class->getFieldMapping('phonenumbers');
        $embeddedMapping  = $class->getFieldMapping('otherPhonenumbers');

        self::assertFalse($referenceMapping['storeEmptyArray']);
        self::assertFalse($embeddedMapping['storeEmptyArray']);
    }

    public function testGridFSMapping(): void
    {
        $class = $this->dm->getClassMetadata(AbstractMappingDriverFile::class);

        self::assertTrue($class->isFile);
        self::assertSame(12345, $class->getChunkSizeBytes());
        self::assertNull($class->customRepositoryClassName);

        self::assertArraySubset([
            'name' => '_id',
            'type' => 'id',
        ], $class->getFieldMapping('id'), true);

        self::assertArraySubset([
            'name' => 'length',
            'type' => 'int',
            'notSaved' => true,
        ], $class->getFieldMapping('size'), true);

        self::assertArraySubset([
            'name' => 'chunkSize',
            'type' => 'int',
            'notSaved' => true,
        ], $class->getFieldMapping('chunkSize'), true);

        self::assertArraySubset([
            'name' => 'filename',
            'type' => 'string',
            'notSaved' => true,
        ], $class->getFieldMapping('name'), true);

        self::assertArraySubset([
            'name' => 'uploadDate',
            'type' => 'date',
            'notSaved' => true,
        ], $class->getFieldMapping('uploadDate'), true);

        self::assertArraySubset([
            'name' => 'metadata',
            'type' => 'one',
            'embedded' => true,
            'targetDocument' => AbstractMappingDriverFileMetadata::class,
        ], $class->getFieldMapping('metadata'), true);
    }

    public function testGridFSMappingWithCustomRepository(): void
    {
        $class = $this->dm->getClassMetadata(AbstractMappingDriverFileWithCustomRepository::class);

        self::assertTrue($class->isFile);
        self::assertSame(AbstractMappingDriverGridFSRepository::class, $class->customRepositoryClassName);
    }

    public function testDuplicateDatabaseNameInMappingCauseErrors(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Field "bar" in class "Doctrine\ODM\MongoDB\Tests\Mapping\AbstractMappingDriverDuplicateDatabaseName" ' .
            'is mapped to field "baz" in the database, but that name is already in use by field "foo".',
        );
        $this->dm->getClassMetadata(AbstractMappingDriverDuplicateDatabaseName::class);
    }

    public function testDuplicateDatabaseNameWithNotSavedDoesNotThrowExeption(): void
    {
        $metadata = $this->dm->getClassMetadata(AbstractMappingDriverDuplicateDatabaseNameNotSaved::class);

        self::assertTrue($metadata->hasField('foo'));
        self::assertTrue($metadata->hasField('bar'));
        self::assertTrue($metadata->fieldMappings['bar']['notSaved']);
    }

    public function testViewWithoutRepository(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid repository class "%s" for mapped class "%s". It must be an instance of "%s".',
            DocumentRepository::class,
            AbstractMappingDriverViewWithoutRepository::class,
            ViewRepository::class,
        ));

        $this->dm->getRepository(AbstractMappingDriverViewWithoutRepository::class);
    }

    public function testViewWithWrongRepository(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid repository class "%s" for mapped class "%s". It must be an instance of "%s".',
            DocumentRepository::class,
            AbstractMappingDriverViewWithWrongRepository::class,
            ViewRepository::class,
        ));

        $this->dm->getRepository(AbstractMappingDriverViewWithWrongRepository::class);
    }

    public function testViewWithoutRootClass(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Class "%s" mapped as view without must have a root class.',
            AbstractMappingDriverViewWithoutRootClass::class,
        ));

        $this->dm->getClassMetadata(AbstractMappingDriverViewWithoutRootClass::class);
    }

    public function testViewWithNonExistingRootClass(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Root class "%s" for view "%s" could not be found.',
            'Doctrine\ODM\MongoDB\LolNo',
            AbstractMappingDriverViewWithNonExistingRootClass::class,
        ));

        $this->dm->getClassMetadata(AbstractMappingDriverViewWithNonExistingRootClass::class);
    }

    public function testView(): void
    {
        $metadata = $this->dm->getClassMetadata(AbstractMappingDriverView::class);

        self::assertEquals('user_name', $metadata->getCollection());
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $metadata->inheritanceType);

        self::assertEquals('id', $metadata->identifier);

        self::assertArraySubset([
            'fieldName' => 'id',
            'id' => true,
            'name' => '_id',
            'type' => 'id',
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
        ], $metadata->fieldMappings['id']);

        self::assertArraySubset([
            'fieldName' => 'name',
            'name' => 'name',
            'type' => 'string',
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ], $metadata->fieldMappings['name']);
    }

    public function testEnumType(): void
    {
        $metadata = $this->dm->getClassMetadata(Card::class);

        self::assertSame(Suit::class, $metadata->fieldMappings['suit']['enumType']);
        self::assertSame('string', $metadata->fieldMappings['suit']['type']);
        self::assertFalse($metadata->fieldMappings['suit']['nullable']);
        self::assertInstanceOf(EnumReflectionProperty::class, $metadata->reflFields['suit']);

        self::assertSame(Suit::class, $metadata->fieldMappings['nullableSuit']['enumType']);
        self::assertSame('string', $metadata->fieldMappings['nullableSuit']['type']);
        self::assertTrue($metadata->fieldMappings['nullableSuit']['nullable']);
        self::assertInstanceOf(EnumReflectionProperty::class, $metadata->reflFields['nullableSuit']);
    }

    public function testTimeSeriesDocumentWithGranularity(): void
    {
        $metadata = $this->dm->getClassMetadata(AbstractMappingDriverTimeSeriesDocumentWithGranularity::class);

        self::assertEquals(
            new ODM\TimeSeries('time', 'metadata', Granularity::Seconds, 86400),
            $metadata->timeSeriesOptions,
        );
    }

    public function testTimeSeriesDocumentWithBucket(): void
    {
        $metadata = $this->dm->getClassMetadata(AbstractMappingDriverTimeSeriesDocumentWithBucket::class);

        self::assertEquals(
            new ODM\TimeSeries('time', 'metadata', expireAfterSeconds: 86400, bucketMaxSpanSeconds: 10, bucketRoundingSeconds: 15),
            $metadata->timeSeriesOptions,
        );
    }
}

/**
 * @ODM\Document(collection="cms_users", writeConcern=1, readOnly=true)
 * @ODM\DiscriminatorField("discr")
 * @ODM\DiscriminatorMap({"default"="Doctrine\ODM\MongoDB\Tests\Mapping\AbstractMappingDriverUser"})
 * @ODM\DefaultDiscriminatorValue("default")
 * @ODM\HasLifecycleCallbacks
 * @ODM\Indexes(@ODM\Index(keys={"createdAt"="asc"},expireAfterSeconds=3600),@ODM\Index(keys={"lock"="asc"},partialFilterExpression={"version"={"$gt"=1},"discr"={"$eq"="default"}}))
 * @ODM\SearchIndex(dynamic=true, analyzer="lucene.standard", searchAnalyzer="lucene.standard", storedSource=true)
 * @ODM\SearchIndex(
 *   name="usernameAndPhoneNumbers",
 *   fields={
 *     "username"={
 *       {
 *         "type"="string",
 *         "multi"={
 *           "english"={"type"="string", "analyzer"="lucene.english"},
 *           "french"={"type"="string", "analyzer"="lucene.french"},
 *         },
 *       },
 *       {"type"="autocomplete"},
 *     },
 *     "embedded_phone_number"={"type"="embeddedDocuments", "dynamic"=true},
 *   },
 *   storedSource={"include"={"username"}},
 *   synonyms={
 *     {"name"="mySynonyms", "analyzer"="lucene.english", "source"={"collection"="synonyms"}},
 *   },
 * )
 * @ODM\ShardKey(keys={"name"="asc"},unique=true,numInitialChunks=4096)
 * @ODM\ReadPreference("primaryPreferred", tags={
 *   { "dc"="east" },
 *   { "dc"="west" },
 *   {  }
 * })
 */
#[ODM\Document(collection: 'cms_users', writeConcern: 1, readOnly: true)]
#[ODM\DiscriminatorField('discr')]
#[ODM\DiscriminatorMap(['default' => AbstractMappingDriverUser::class])]
#[ODM\DefaultDiscriminatorValue('default')]
#[ODM\HasLifecycleCallbacks]
#[ODM\Index(keys: ['createdAt' => 'asc'], expireAfterSeconds: 3600)]
#[ODM\Index(keys: ['lock' => 'asc'], partialFilterExpression: ['version' => ['$gt' => 1], 'discr' => ['$eq' => 'default']])]
#[ODM\SearchIndex(dynamic: true, analyzer: 'lucene.standard', searchAnalyzer: 'lucene.standard', storedSource: true)]
#[ODM\SearchIndex(
    name: 'usernameAndPhoneNumbers',
    fields: [
        'username' => [
            [
                'type' => 'string',
                'multi' => [
                    'english' => ['type' => 'string', 'analyzer' => 'lucene.english'],
                    'french' => ['type' => 'string', 'analyzer' => 'lucene.french'],
                ],
            ],
            ['type' => 'autocomplete'],
        ],
        'embedded_phone_number' => ['type' => 'embeddedDocuments', 'dynamic' => true],
    ],
    storedSource: ['include' => ['username']],
    synonyms: [
        ['name' => 'mySynonyms', 'analyzer' => 'lucene.english', 'source' => ['collection' => 'synonyms']],
    ],
)]
#[ODM\ShardKey(keys: ['name' => 'asc'], unique: true, numInitialChunks: 4096)]
#[ODM\ReadPreference('primaryPreferred', tags: [['dc' => 'east'], ['dc' => 'west'], []])]
class AbstractMappingDriverUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id()]
    public $identifier;

    /**
     * @ODM\Version
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    #[ODM\Version]
    #[ODM\Field(type: 'int')]
    public $version;

    /**
     * @ODM\Lock
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    #[ODM\Lock]
    #[ODM\Field(type: 'int')]
    public $lock;

    /**
     * @ODM\Field(name="username", type="string")
     * @ODM\UniqueIndex(order="desc")
     *
     * @var string|null
     */
    #[ODM\Field(name: 'username', type: 'string')]
    #[ODM\UniqueIndex(order: 'desc')]
    public $name;

    /**
     * @ODM\Field(type="string")
     * @ODM\UniqueIndex(order="desc")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    #[ODM\UniqueIndex(order: 'desc')]
    public $email;

    /**
     * @ODM\Field(type="int")
     * @ODM\UniqueIndex(order="desc")
     *
     * @var int|null
     */
    #[ODM\Field(type: 'int')]
    #[ODM\UniqueIndex(order: 'desc')]
    public $mysqlProfileId;

    /**
     * @ODM\ReferenceOne(targetDocument=Address::class, cascade={"remove"})
     *
     * @var Address|null
     */
    #[ODM\ReferenceOne(targetDocument: Address::class, cascade: ['remove'])]
    public $address;

    /**
     * @ODM\ReferenceMany(collectionClass=PhonenumberCollection::class, cascade={"persist"}, discriminatorField="discr", discriminatorMap={"home"=HomePhonenumber::class, "work"=WorkPhonenumber::class}, defaultDiscriminatorValue="home")
     *
     * @var PhonenumberCollection
     */
    #[ODM\ReferenceMany(collectionClass: PhonenumberCollection::class, cascade: ['persist'], discriminatorField: 'discr', discriminatorMap: ['home' => HomePhonenumber::class, 'work' => WorkPhonenumber::class], defaultDiscriminatorValue: 'home')]
    public $phonenumbers;

    /**
     * @ODM\ReferenceMany(targetDocument=Group::class, cascade={"all"})
     *
     * @var Collection<int, Group>
     */
    #[ODM\ReferenceMany(targetDocument: Group::class, cascade: ['all'])]
    public $groups;

    /**
     * @ODM\ReferenceMany(targetDocument=Phonenumber::class, collectionClass=PhonenumberCollection::class, name="more_phone_numbers")
     *
     * @var PhonenumberCollection
     */
    #[ODM\ReferenceMany(targetDocument: Phonenumber::class, collectionClass: PhonenumberCollection::class, name: 'more_phone_numbers')]
    public $morePhoneNumbers;

    /**
     * @ODM\EmbedMany(targetDocument=Phonenumber::class, name="embedded_phone_number")
     *
     * @var Collection<int, Phonenumber>
     */
    #[ODM\EmbedMany(targetDocument: Phonenumber::class, name: 'embedded_phone_number')]
    public $embeddedPhonenumber;

    /**
     * @ODM\EmbedMany(discriminatorField="discr", discriminatorMap={"home"=HomePhonenumber::class, "work"=WorkPhonenumber::class}, defaultDiscriminatorValue="home")
     *
     * @var Collection<int, HomePhonenumber|WorkPhonenumber>
     */
    #[ODM\EmbedMany(discriminatorField: 'discr', discriminatorMap: ['home' => HomePhonenumber::class, 'work' => WorkPhonenumber::class], defaultDiscriminatorValue: 'home')]
    public $otherPhonenumbers;

    /**
     * @ODM\Field(type="date")
     *
     * @var DateTime|null
     */
    #[ODM\Field(type: 'date')]
    public $createdAt;

    /**
     * @ODM\Field(type="collection")
     *
     * @var string[]
     */
    #[ODM\Field(type: 'collection')]
    public $roles = [];

    /** @ODM\PrePersist */
    #[ODM\PrePersist]
    public function doStuffOnPrePersist(): void
    {
    }

    /** @ODM\PrePersist */
    #[ODM\PrePersist]
    public function doOtherStuffOnPrePersistToo(): void
    {
    }

    /** @ODM\PostPersist */
    #[ODM\PostPersist]
    public function doStuffOnPostPersist(): void
    {
    }

    /** @param ClassMetadata<AbstractMappingDriverUser> $metadata */
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
        $metadata->setCollection('cms_users');
        $metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
        $metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
        $metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');
        $metadata->setDiscriminatorField(['fieldName' => 'discr']);
        $metadata->setDiscriminatorMap(['default' => self::class]);
        $metadata->setDefaultDiscriminatorValue('default');
        $metadata->mapField([
            'id' => true,
            'fieldName' => 'id',
        ]);
        $metadata->mapField([
            'fieldName' => 'version',
            'type' => 'int',
            'version' => true,
        ]);
        $metadata->mapField([
            'fieldName' => 'lock',
            'type' => 'int',
            'lock' => true,
        ]);
        $metadata->mapField([
            'fieldName' => 'name',
            'name' => 'username',
            'type' => 'string',
        ]);
        $metadata->mapField([
            'fieldName' => 'email',
            'type' => 'string',
        ]);
        $metadata->mapField([
            'fieldName' => 'mysqlProfileId',
            'type' => 'integer',
        ]);
        $metadata->mapOneReference([
            'fieldName' => 'address',
            'targetDocument' => Address::class,
            'cascade' => ['remove'],
        ]);
        $metadata->mapManyReference([
            'fieldName' => 'phonenumbers',
            'targetDocument' => Phonenumber::class,
            'collectionClass' => PhonenumberCollection::class,
            'cascade' => ['persist'],
            'discriminatorField' => 'discr',
            'discriminatorMap' => [
                'home' => HomePhonenumber::class,
                'work' => WorkPhonenumber::class,
            ],
            'defaultDiscriminatorValue' => 'home',
        ]);
        $metadata->mapManyReference([
            'fieldName' => 'morePhoneNumbers',
            'name' => 'more_phone_numbers',
            'targetDocument' => Phonenumber::class,
            'collectionClass' => PhonenumberCollection::class,
        ]);
        $metadata->mapManyReference([
            'fieldName' => 'groups',
            'targetDocument' => Group::class,
            'cascade' => [
                'remove',
                'persist',
                'refresh',
                'merge',
                'detach',
            ],
        ]);
        $metadata->mapOneEmbedded([
            'fieldName' => 'embeddedPhonenumber',
            'name' => 'embedded_phone_number',
        ]);
        $metadata->mapManyEmbedded([
            'fieldName' => 'otherPhonenumbers',
            'targetDocument' => Phonenumber::class,
            'discriminatorField' => 'discr',
            'discriminatorMap' => [
                'home' => HomePhonenumber::class,
                'work' => WorkPhonenumber::class,
            ],
            'defaultDiscriminatorValue' => 'home',
        ]);
        $metadata->addIndex(['username' => 'desc'], ['unique' => true]);
        $metadata->addIndex(['email' => 'desc'], ['unique' => true]);
        $metadata->addIndex(['mysqlProfileId' => 'desc'], ['unique' => true]);
        $metadata->addIndex(['createdAt' => 'asc'], ['expireAfterSeconds' => 3600]);
        $metadata->setShardKey(['name' => 'asc'], ['unique' => true, 'numInitialChunks' => 4096]);
    }
}

/** @template-extends ArrayCollection<int, Phonenumber> */
class PhonenumberCollection extends ArrayCollection
{
}

class HomePhonenumber
{
}

class WorkPhonenumber
{
}

class Address
{
}

class Group
{
}

class Phonenumber
{
}

class InvalidMappingDocument
{
    /** @var string|null */
    public $id;
}

/** @ODM\File(chunkSizeBytes=12345) */
#[ODM\File(chunkSizeBytes: 12345)]
class AbstractMappingDriverFile
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;

    /**
     * @ODM\File\Length
     *
     * @var int|null
     */
    #[ODM\File\Length]
    public $size;

    /**
     * @ODM\File\ChunkSize
     *
     * @var int|null
     */
    #[ODM\File\ChunkSize]
    public $chunkSize;

    /**
     * @ODM\File\Filename
     *
     * @var string|null
     */
    #[ODM\File\Filename]
    public $name;

    /**
     * @ODM\File\Metadata(targetDocument=AbstractMappingDriverFileMetadata::class)
     *
     * @var AbstractMappingDriverFileMetadata|null
     */
    #[ODM\File\Metadata(targetDocument: AbstractMappingDriverFileMetadata::class)]
    public $metadata;

    /**
     * @ODM\File\UploadDate
     *
     * @var DateTime|null
     */
    #[ODM\File\UploadDate]
    public $uploadDate;
}

class AbstractMappingDriverFileMetadata
{
    /**
     * @ODM\Field
     *
     * @var string|null
     */
    #[ODM\Field]
    public $contentType;
}

/** @ODM\File(repositoryClass=AbstractMappingDriverGridFSRepository::class) */
#[ODM\File(repositoryClass: AbstractMappingDriverGridFSRepository::class)]
class AbstractMappingDriverFileWithCustomRepository
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;
}

/** @template-extends DefaultGridFSRepository<AbstractMappingDriverFileWithCustomRepository> */
class AbstractMappingDriverGridFSRepository extends DefaultGridFSRepository
{
}

/** @ODM\MappedSuperclass */
#[ODM\MappedSuperclass]
class AbstractMappingDriverSuperClass
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|int|null
     */
    #[ODM\Field(type: 'string')]
    protected $override;
}

/** @ODM\Document */
#[ODM\Document]
class AbstractMappingDriverDuplicateDatabaseName extends AbstractMappingDriverSuperClass
{
    /**
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    #[ODM\Field(type: 'int')]
    public $override;

    /**
     * @ODM\Field(type="string", name="baz")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string', name: 'baz')]
    public $foo;

    /**
     * @ODM\Field(type="string", name="baz")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string', name: 'baz')]
    public $bar;
}

/** @ODM\Document */
#[ODM\Document]
class AbstractMappingDriverDuplicateDatabaseNameNotSaved extends AbstractMappingDriverSuperClass
{
    /**
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    #[ODM\Field(type: 'int')]
    public $override;

    /**
     * @ODM\Field(type="string", name="baz")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'int', name: 'baz')]
    public $foo;

    /**
     * @ODM\Field(type="string", name="baz", notSaved=true)
     *
     * @var string|null
     */
    #[ODM\Field(type: 'int', name: 'baz', notSaved: true)]
    public $bar;
}

/** @ODM\View(rootClass=AbstractMappingDriverUser::class) */
#[ODM\View(rootClass: AbstractMappingDriverUser::class)]
class AbstractMappingDriverViewWithoutRepository
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    public $name;
}

/** @ODM\View(repositoryClass=DocumentRepository::class, rootClass=AbstractMappingDriverUser::class) */
#[ODM\View(repositoryClass: DocumentRepository::class, rootClass: AbstractMappingDriverUser::class)]
class AbstractMappingDriverViewWithWrongRepository
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    public $name;
}

/** @ODM\View(repositoryClass=AbstractMappingDriverViewRepository::class) */
#[ODM\View(repositoryClass: AbstractMappingDriverViewRepository::class)]
class AbstractMappingDriverViewWithoutRootClass
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    public $name;
}

/** @ODM\View(repositoryClass=AbstractMappingDriverViewRepository::class, rootClass="Doctrine\ODM\MongoDB\LolNo") */
#[ODM\View(repositoryClass: AbstractMappingDriverViewRepository::class, rootClass: 'Doctrine\ODM\MongoDB\LolNo')]
class AbstractMappingDriverViewWithNonExistingRootClass
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    public $name;
}

/**
 * @ODM\View(
 *     repositoryClass=AbstractMappingDriverViewRepository::class,
 *     rootClass=AbstractMappingDriverUser::class,
 *     view="user_name",
 * )
 */
#[ODM\View(repositoryClass: AbstractMappingDriverViewRepository::class, rootClass: AbstractMappingDriverUser::class, view: 'user_name')]
class AbstractMappingDriverView
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    public $name;
}

/**
 * @template-extends DocumentRepository<AbstractMappingDriverViewWithoutRootClass>
 * @template-implements  ViewRepository<AbstractMappingDriverViewWithoutRootClass>
 */
class AbstractMappingDriverViewRepository extends DocumentRepository implements ViewRepository
{
    public function createViewAggregation(Builder $builder): void
    {
        $builder
            ->project()
                ->includeFields(['name']);
    }
}

/**
 * @ODM\Document(collection="cms_users", writeConcern=1, readOnly=true)
 * @ODM\TimeSeries(timeField="time", metaField="metadata", granularity=Granularity::Seconds, expireAfterSeconds=86400)
 */
#[ODM\Document]
#[ODM\TimeSeries(timeField: 'time', metaField: 'metadata', granularity: Granularity::Seconds, expireAfterSeconds: 86400)]
class AbstractMappingDriverTimeSeriesDocumentWithGranularity
{
    /** @ODM\Id */
    #[ODM\Id]
    public ?string $id = null;

    /** @ODM\Field(type="date") */
    #[ODM\Field(type: 'date')]
    public DateTime $time;

    /** @ODM\Field */
    #[ODM\Field]
    public string $metadata;

    /** @ODM\Field(type="int") */
    #[ODM\Field(type: 'int')]
    public int $value;
}

/**
 * @ODM\Document(collection="cms_users", writeConcern=1, readOnly=true)
 * @ODM\TimeSeries(timeField="time", metaField="metadata", expireAfterSeconds=86400, bucketMaxSpanSeconds=10, bucketRoundingSeconds=15)
 */
#[ODM\Document]
#[ODM\TimeSeries(timeField: 'time', metaField: 'metadata', expireAfterSeconds: 86400, bucketMaxSpanSeconds: 10, bucketRoundingSeconds: 15)]
class AbstractMappingDriverTimeSeriesDocumentWithBucket
{
    /** @ODM\Id */
    #[ODM\Id]
    public ?string $id = null;

    /** @ODM\Field(type="date") */
    #[ODM\Field(type: 'date')]
    public DateTime $time;

    /** @ODM\Field */
    #[ODM\Field]
    public string $metadata;

    /** @ODM\Field(type="int") */
    #[ODM\Field(type: 'int')]
    public int $value;
}
