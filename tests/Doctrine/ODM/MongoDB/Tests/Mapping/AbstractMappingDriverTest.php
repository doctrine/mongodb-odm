<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\ViewRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use InvalidArgumentException;

use function key;
use function sprintf;
use function strcmp;
use function usort;

abstract class AbstractMappingDriverTest extends BaseTest
{
    abstract protected function loadDriver();

    protected function createMetadataDriverImpl()
    {
        return $this->loadDriver();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testLoadMapping()
    {
        return $this->dm->getClassMetadata(AbstractMappingDriverUser::class);
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testLoadMapping
     */
    public function testDocumentCollectionNameAndInheritance($class)
    {
        $this->assertEquals('cms_users', $class->getCollection());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $class->inheritanceType);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testLoadMapping
     */
    public function testDocumentMarkedAsReadOnly($class)
    {
        $this->assertTrue($class->isReadOnly);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testDocumentCollectionNameAndInheritance
     */
    public function testDocumentLevelReadPreference($class)
    {
        $this->assertEquals('primaryPreferred', $class->readPreference);
        $this->assertEquals([
            ['dc' => 'east'],
            ['dc' => 'west'],
            [],
        ], $class->readPreferenceTags);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testDocumentCollectionNameAndInheritance
     */
    public function testDocumentLevelWriteConcern($class)
    {
        $this->assertEquals(1, $class->getWriteConcern());

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testDocumentLevelWriteConcern
     */
    public function testFieldMappings($class)
    {
        $this->assertCount(14, $class->fieldMappings);
        $this->assertTrue(isset($class->fieldMappings['identifier']));
        $this->assertTrue(isset($class->fieldMappings['version']));
        $this->assertTrue(isset($class->fieldMappings['lock']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertTrue(isset($class->fieldMappings['email']));
        $this->assertTrue(isset($class->fieldMappings['roles']));

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testDocumentCollectionNameAndInheritance
     */
    public function testAssociationMappings($class)
    {
        $this->assertCount(6, $class->associationMappings);
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue(isset($class->associationMappings['morePhoneNumbers']));
        $this->assertTrue(isset($class->associationMappings['embeddedPhonenumber']));
        $this->assertTrue(isset($class->associationMappings['otherPhonenumbers']));
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testDocumentCollectionNameAndInheritance
     */
    public function testGetAssociationTargetClass($class)
    {
        $this->assertEquals(Address::class, $class->getAssociationTargetClass('address'));
        $this->assertEquals(Group::class, $class->getAssociationTargetClass('groups'));
        $this->assertNull($class->getAssociationTargetClass('phonenumbers'));
        $this->assertEquals(Phonenumber::class, $class->getAssociationTargetClass('morePhoneNumbers'));
        $this->assertEquals(Phonenumber::class, $class->getAssociationTargetClass('embeddedPhonenumber'));
        $this->assertNull($class->getAssociationTargetClass('otherPhonenumbers'));
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testDocumentCollectionNameAndInheritance
     */
    public function testGetAssociationTargetClassThrowsExceptionWhenEmpty($class)
    {
        $this->expectException(InvalidArgumentException::class);
        $class->getAssociationTargetClass('invalid_association');
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testDocumentCollectionNameAndInheritance
     */
    public function testStringFieldMappings($class)
    {
        $this->assertEquals('string', $class->fieldMappings['name']['type']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testFieldMappings
     */
    public function testIdentifier($class)
    {
        $this->assertEquals('identifier', $class->identifier);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testFieldMappings
     */
    public function testVersionFieldMappings($class)
    {
        $this->assertEquals('int', $class->fieldMappings['version']['type']);
        $this->assertNotEmpty($class->fieldMappings['version']['version']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testFieldMappings
     */
    public function testLockFieldMappings($class)
    {
        $this->assertEquals('int', $class->fieldMappings['lock']['type']);
        $this->assertNotEmpty($class->fieldMappings['lock']['lock']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testIdentifier
     */
    public function testAssocations($class)
    {
        $this->assertCount(14, $class->fieldMappings);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testAssocations
     */
    public function testOwningOneToOneAssocation($class)
    {
        $this->assertTrue(isset($class->fieldMappings['address']));
        $this->assertIsArray($class->fieldMappings['address']);
        // Check cascading
        $this->assertTrue($class->fieldMappings['address']['isCascadeRemove']);
        $this->assertFalse($class->fieldMappings['address']['isCascadePersist']);
        $this->assertFalse($class->fieldMappings['address']['isCascadeRefresh']);
        $this->assertFalse($class->fieldMappings['address']['isCascadeDetach']);
        $this->assertFalse($class->fieldMappings['address']['isCascadeMerge']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testOwningOneToOneAssocation
     */
    public function testLifecycleCallbacks($class)
    {
        $expectedLifecycleCallbacks = [
            'prePersist' => ['doStuffOnPrePersist', 'doOtherStuffOnPrePersistToo'],
            'postPersist' => ['doStuffOnPostPersist'],
        ];

        $this->assertEquals($expectedLifecycleCallbacks, $class->lifecycleCallbacks);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testLifecycleCallbacks
     */
    public function testCustomFieldName($class)
    {
        $this->assertEquals('name', $class->fieldMappings['name']['fieldName']);
        $this->assertEquals('username', $class->fieldMappings['name']['name']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testCustomFieldName
     */
    public function testCustomReferenceFieldName($class)
    {
        $this->assertEquals('morePhoneNumbers', $class->fieldMappings['morePhoneNumbers']['fieldName']);
        $this->assertEquals('more_phone_numbers', $class->fieldMappings['morePhoneNumbers']['name']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testCustomReferenceFieldName
     */
    public function testCustomEmbedFieldName($class)
    {
        $this->assertEquals('embeddedPhonenumber', $class->fieldMappings['embeddedPhonenumber']['fieldName']);
        $this->assertEquals('embedded_phone_number', $class->fieldMappings['embeddedPhonenumber']['name']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testCustomEmbedFieldName
     */
    public function testDiscriminator($class)
    {
        $this->assertTrue(isset($class->discriminatorField));
        $this->assertTrue(isset($class->discriminatorMap));
        $this->assertTrue(isset($class->defaultDiscriminatorValue));
        $this->assertEquals('discr', $class->discriminatorField);
        $this->assertEquals(['default' => AbstractMappingDriverUser::class], $class->discriminatorMap);
        $this->assertEquals('default', $class->defaultDiscriminatorValue);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testDiscriminator
     */
    public function testEmbedDiscriminator($class)
    {
        $this->assertTrue(isset($class->fieldMappings['otherPhonenumbers']['discriminatorField']));
        $this->assertTrue(isset($class->fieldMappings['otherPhonenumbers']['discriminatorMap']));
        $this->assertTrue(isset($class->fieldMappings['otherPhonenumbers']['defaultDiscriminatorValue']));
        $this->assertEquals('discr', $class->fieldMappings['otherPhonenumbers']['discriminatorField']);
        $this->assertEquals([
            'home' => HomePhonenumber::class,
            'work' => WorkPhonenumber::class,
        ], $class->fieldMappings['otherPhonenumbers']['discriminatorMap']);
        $this->assertEquals('home', $class->fieldMappings['otherPhonenumbers']['defaultDiscriminatorValue']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testEmbedDiscriminator
     */
    public function testReferenceDiscriminator($class)
    {
        $this->assertTrue(isset($class->fieldMappings['phonenumbers']['discriminatorField']));
        $this->assertTrue(isset($class->fieldMappings['phonenumbers']['discriminatorMap']));
        $this->assertTrue(isset($class->fieldMappings['phonenumbers']['defaultDiscriminatorValue']));
        $this->assertEquals('discr', $class->fieldMappings['phonenumbers']['discriminatorField']);
        $this->assertEquals([
            'home' => HomePhonenumber::class,
            'work' => WorkPhonenumber::class,
        ], $class->fieldMappings['phonenumbers']['discriminatorMap']);
        $this->assertEquals('home', $class->fieldMappings['phonenumbers']['defaultDiscriminatorValue']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testCustomFieldName
     */
    public function testIndexes($class)
    {
        $indexes = $class->indexes;

        /* Sort indexes by their first fieldname. This is necessary since the
         * index registration order may differ among drivers.
         */
        $this->assertTrue(usort($indexes, static function (array $a, array $b) {
            return strcmp(key($a['keys']), key($b['keys']));
        }));

        $this->assertTrue(isset($indexes[0]['keys']['createdAt']));
        $this->assertEquals(1, $indexes[0]['keys']['createdAt']);
        $this->assertNotEmpty($indexes[0]['options']);
        $this->assertTrue(isset($indexes[0]['options']['expireAfterSeconds']));
        $this->assertSame(3600, $indexes[0]['options']['expireAfterSeconds']);

        $this->assertTrue(isset($indexes[1]['keys']['email']));
        $this->assertEquals(-1, $indexes[1]['keys']['email']);
        $this->assertNotEmpty($indexes[1]['options']);
        $this->assertTrue(isset($indexes[1]['options']['unique']));
        $this->assertEquals(true, $indexes[1]['options']['unique']);

        $this->assertTrue(isset($indexes[2]['keys']['lock']));
        $this->assertEquals(1, $indexes[2]['keys']['lock']);
        $this->assertNotEmpty($indexes[2]['options']);
        $this->assertTrue(isset($indexes[2]['options']['partialFilterExpression']));
        $this->assertSame(['version' => ['$gt' => 1], 'discr' => ['$eq' => 'default']], $indexes[2]['options']['partialFilterExpression']);

        $this->assertTrue(isset($indexes[3]['keys']['mysqlProfileId']));
        $this->assertEquals(-1, $indexes[3]['keys']['mysqlProfileId']);
        $this->assertNotEmpty($indexes[3]['options']);
        $this->assertTrue(isset($indexes[3]['options']['unique']));
        $this->assertEquals(true, $indexes[3]['options']['unique']);

        $this->assertTrue(isset($indexes[4]['keys']['username']));
        $this->assertEquals(-1, $indexes[4]['keys']['username']);
        $this->assertTrue(isset($indexes[4]['options']['unique']));
        $this->assertEquals(true, $indexes[4]['options']['unique']);

        return $class;
    }

    /**
     * @param ClassMetadata $class
     *
     * @depends testIndexes
     */
    public function testShardKey($class)
    {
        $shardKey = $class->getShardKey();

        $this->assertTrue(isset($shardKey['keys']['name']), 'Shard key is not mapped');
        $this->assertEquals(1, $shardKey['keys']['name'], 'Wrong value for shard key');

        $this->assertTrue(isset($shardKey['options']['unique']), 'Shard key option is not mapped');
        $this->assertTrue($shardKey['options']['unique'], 'Shard key option has wrong value');
        $this->assertTrue(isset($shardKey['options']['numInitialChunks']), 'Shard key option is not mapped');
        $this->assertEquals(4096, $shardKey['options']['numInitialChunks'], 'Shard key option has wrong value');
    }

    public function testGridFSMapping()
    {
        $class = $this->dm->getClassMetadata(AbstractMappingDriverFile::class);

        $this->assertTrue($class->isFile);
        $this->assertSame(12345, $class->getChunkSizeBytes());
        $this->assertNull($class->customRepositoryClassName);

        $this->assertArraySubset([
            'name' => '_id',
            'type' => 'id',
        ], $class->getFieldMapping('id'), true);

        $this->assertArraySubset([
            'name' => 'length',
            'type' => 'int',
            'notSaved' => true,
        ], $class->getFieldMapping('size'), true);

        $this->assertArraySubset([
            'name' => 'chunkSize',
            'type' => 'int',
            'notSaved' => true,
        ], $class->getFieldMapping('chunkSize'), true);

        $this->assertArraySubset([
            'name' => 'filename',
            'type' => 'string',
            'notSaved' => true,
        ], $class->getFieldMapping('name'), true);

        $this->assertArraySubset([
            'name' => 'uploadDate',
            'type' => 'date',
            'notSaved' => true,
        ], $class->getFieldMapping('uploadDate'), true);

        $this->assertArraySubset([
            'name' => 'metadata',
            'type' => 'one',
            'embedded' => true,
            'targetDocument' => AbstractMappingDriverFileMetadata::class,
        ], $class->getFieldMapping('metadata'), true);
    }

    public function testGridFSMappingWithCustomRepository()
    {
        $class = $this->dm->getClassMetadata(AbstractMappingDriverFileWithCustomRepository::class);

        $this->assertTrue($class->isFile);
        $this->assertSame(AbstractMappingDriverGridFSRepository::class, $class->customRepositoryClassName);
    }

    public function testDuplicateDatabaseNameInMappingCauseErrors()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Field "bar" in class "Doctrine\ODM\MongoDB\Tests\Mapping\AbstractMappingDriverDuplicateDatabaseName" ' .
            'is mapped to field "baz" in the database, but that name is already in use by field "foo".'
        );
        $this->dm->getClassMetadata(AbstractMappingDriverDuplicateDatabaseName::class);
    }

    public function testDuplicateDatabaseNameWithNotSavedDoesNotThrowExeption()
    {
        $metadata = $this->dm->getClassMetadata(AbstractMappingDriverDuplicateDatabaseNameNotSaved::class);

        $this->assertTrue($metadata->hasField('foo'));
        $this->assertTrue($metadata->hasField('bar'));
        $this->assertTrue($metadata->fieldMappings['bar']['notSaved']);
    }

    public function testViewWithoutRepository()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid repository class "%s" for mapped class "%s". It must be an instance of "%s".',
            DocumentRepository::class,
            AbstractMappingDriverViewWithoutRepository::class,
            ViewRepository::class
        ));

        $this->dm->getRepository(AbstractMappingDriverViewWithoutRepository::class);
    }

    public function testViewWithWrongRepository()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid repository class "%s" for mapped class "%s". It must be an instance of "%s".',
            DocumentRepository::class,
            AbstractMappingDriverViewWithWrongRepository::class,
            ViewRepository::class
        ));

        $this->dm->getRepository(AbstractMappingDriverViewWithWrongRepository::class);
    }

    public function testViewWithoutRootClass()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Class "%s" mapped as view without must have a root class.',
            AbstractMappingDriverViewWithoutRootClass::class
        ));

        $this->dm->getClassMetadata(AbstractMappingDriverViewWithoutRootClass::class);
    }

    public function testViewWithNonExistingRootClass()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Root class "%s" for view "%s" could not be found.',
            'Doctrine\ODM\MongoDB\LolNo',
            AbstractMappingDriverViewWithNonExistingRootClass::class
        ));

        $this->dm->getClassMetadata(AbstractMappingDriverViewWithNonExistingRootClass::class);
    }

    public function testView()
    {
        $metadata = $this->dm->getClassMetadata(AbstractMappingDriverView::class);

        $this->assertEquals('user_name', $metadata->getCollection());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $metadata->inheritanceType);

        $this->assertEquals('id', $metadata->identifier);

        $this->assertArraySubset([
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

        $this->assertArraySubset([
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
}

/**
 * @ODM\Document(collection="cms_users", writeConcern=1, readOnly=true)
 * @ODM\DiscriminatorField("discr")
 * @ODM\DiscriminatorMap({"default"="Doctrine\ODM\MongoDB\Tests\Mapping\AbstractMappingDriverUser"})
 * @ODM\DefaultDiscriminatorValue("default")
 * @ODM\HasLifecycleCallbacks
 * @ODM\Indexes(@ODM\Index(keys={"createdAt"="asc"},expireAfterSeconds=3600),@ODM\Index(keys={"lock"="asc"},partialFilterExpression={"version"={"$gt"=1},"discr"={"$eq"="default"}}))
 * @ODM\ShardKey(keys={"name"="asc"},unique=true,numInitialChunks=4096)
 * @ODM\ReadPreference("primaryPreferred", tags={
 *   { "dc"="east" },
 *   { "dc"="west" },
 *   {  }
 * })
 */
class AbstractMappingDriverUser
{
    /** @ODM\Id */
    public $identifier;

    /**
     * @ODM\Version
     * @ODM\Field(type="int")
     */
    public $version;

    /**
     * @ODM\Lock
     * @ODM\Field(type="int")
     */
    public $lock;

    /**
     * @ODM\Field(name="username", type="string")
     * @ODM\UniqueIndex(order="desc")
     */
    public $name;

    /**
     * @ODM\Field(type="string")
     * @ODM\UniqueIndex(order="desc")
     */
    public $email;

    /**
     * @ODM\Field(type="int")
     * @ODM\UniqueIndex(order="desc")
     */
    public $mysqlProfileId;

    /** @ODM\ReferenceOne(targetDocument=Address::class, cascade={"remove"}) */
    public $address;

    /** @ODM\ReferenceMany(collectionClass=PhonenumberCollection::class, cascade={"persist"}, discriminatorField="discr", discriminatorMap={"home"=HomePhonenumber::class, "work"=WorkPhonenumber::class}, defaultDiscriminatorValue="home") */
    public $phonenumbers;

    /** @ODM\ReferenceMany(targetDocument=Group::class, cascade={"all"}) */
    public $groups;

    /** @ODM\ReferenceMany(targetDocument=Phonenumber::class, collectionClass=PhonenumberCollection::class, name="more_phone_numbers") */
    public $morePhoneNumbers;

    /** @ODM\EmbedMany(targetDocument=Phonenumber::class, name="embedded_phone_number") */
    public $embeddedPhonenumber;

    /** @ODM\EmbedMany(discriminatorField="discr", discriminatorMap={"home"=HomePhonenumber::class, "work"=WorkPhonenumber::class}, defaultDiscriminatorValue="home") */
    public $otherPhonenumbers;

    /** @ODM\Field(type="date") */
    public $createdAt;

    /** @ODM\Field(type="collection") */
    public $roles = [];

    /**
     * @ODM\PrePersist
     */
    public function doStuffOnPrePersist()
    {
    }

    /**
     * @ODM\PrePersist
     */
    public function doOtherStuffOnPrePersistToo()
    {
    }

    /**
     * @ODM\PostPersist
     */
    public function doStuffOnPostPersist()
    {
    }

    public static function loadMetadata(ClassMetadata $metadata)
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
            'cascade' => [0 => 'remove'],
        ]);
        $metadata->mapManyReference([
            'fieldName' => 'phonenumbers',
            'targetDocument' => Phonenumber::class,
            'collectionClass' => PhonenumberCollection::class,
            'cascade' => [1 => 'persist'],
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
                0 => 'remove',
                1 => 'persist',
                2 => 'refresh',
                3 => 'merge',
                4 => 'detach',
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
    public $id;
}

/**
 * @ODM\File(chunkSizeBytes=12345)
 */
class AbstractMappingDriverFile
{
    /** @ODM\Id */
    public $id;

    /** @ODM\File\Length */
    public $size;

    /** @ODM\File\ChunkSize */
    public $chunkSize;

    /** @ODM\File\Filename */
    public $name;

    /** @ODM\File\Metadata(targetDocument=AbstractMappingDriverFileMetadata::class) */
    public $metadata;

    /** @ODM\File\UploadDate */
    public $uploadDate;
}

class AbstractMappingDriverFileMetadata
{
    /** @ODM\Field */
    public $contentType;
}

/**
 * @ODM\File(repositoryClass=AbstractMappingDriverGridFSRepository::class)
 */
class AbstractMappingDriverFileWithCustomRepository
{
    /** @ODM\Id */
    public $id;
}

class AbstractMappingDriverGridFSRepository extends DefaultGridFSRepository
{
}

/** @ODM\MappedSuperclass */
class AbstractMappingDriverSuperClass
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    protected $override;
}

/**
 * @ODM\Document
 */
class AbstractMappingDriverDuplicateDatabaseName extends AbstractMappingDriverSuperClass
{
    /** @ODM\Field(type="int") */
    public $override;

    /** @ODM\Field(type="string", name="baz") */
    public $foo;

    /** @ODM\Field(type="string", name="baz") */
    public $bar;
}

/**
 * @ODM\Document
 */
class AbstractMappingDriverDuplicateDatabaseNameNotSaved extends AbstractMappingDriverSuperClass
{
    /** @ODM\Field(type="int") */
    public $override;

    /** @ODM\Field(type="string", name="baz") */
    public $foo;

    /** @ODM\Field(type="string", name="baz", notSaved=true) */
    public $bar;
}

/**
 * @ODM\View(rootClass=AbstractMappingDriverUser::class)
 */
class AbstractMappingDriverViewWithoutRepository
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/**
 * @ODM\View(repositoryClass=DocumentRepository::class, rootClass=AbstractMappingDriverUser::class)
 */
class AbstractMappingDriverViewWithWrongRepository
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/**
 * @ODM\View(repositoryClass=AbstractMappingDriverViewRepository::class)
 */
class AbstractMappingDriverViewWithoutRootClass
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/**
 * @ODM\View(repositoryClass=AbstractMappingDriverViewRepository::class, rootClass="Doctrine\ODM\MongoDB\LolNo")
 */
class AbstractMappingDriverViewWithNonExistingRootClass
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/**
 * @ODM\View(
 *     repositoryClass=AbstractMappingDriverViewRepository::class,
 *     rootClass=AbstractMappingDriverUser::class,
 *     view="user_name",
 * )
 */
class AbstractMappingDriverView
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

class AbstractMappingDriverViewRepository extends DocumentRepository implements ViewRepository
{
    public function createViewAggregation(Builder $builder): void
    {
        $builder
            ->project()
                ->includeFields(['name']);
    }
}
