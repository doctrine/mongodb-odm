<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Documents\Account;
use Documents\Address;
use Documents\Group;
use Documents\Phonenumber;
use Documents\Profile;
use PHPUnit\Framework\TestCase;
use TestDocuments\EmbeddedDocument;
use TestDocuments\NullableFieldsDocument;
use TestDocuments\PartialFilterDocument;
use TestDocuments\PrimedCollectionDocument;
use TestDocuments\QueryResultDocument;
use TestDocuments\User;

abstract class AbstractDriverTest extends TestCase
{
    /** @var MappingDriver|null */
    protected $driver;

    public function setUp(): void
    {
        // implement driver setup and metadata read
    }

    public function tearDown(): void
    {
        unset($this->driver);
    }

    public function testDriver(): void
    {
        $classMetadata = new ClassMetadata(User::class);
        $this->driver->loadMetadataForClass(User::class, $classMetadata);

        self::assertEquals([
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
        ], $classMetadata->fieldMappings['id']);

        self::assertEquals([
            'fieldName' => 'username',
            'name' => 'username',
            'type' => 'string',
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'unique' => true,
            'sparse' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ], $classMetadata->fieldMappings['username']);

        self::assertEquals([
            [
                'keys' => ['username' => 1],
                'options' => ['unique' => true, 'sparse' => true],
            ],
        ], $classMetadata->getIndexes());

        self::assertEquals([
            'fieldName' => 'createdAt',
            'name' => 'createdAt',
            'type' => 'date',
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ], $classMetadata->fieldMappings['createdAt']);

        self::assertEquals([
            'fieldName' => 'tags',
            'name' => 'tags',
            'type' => 'collection',
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ], $classMetadata->fieldMappings['tags']);

        self::assertEquals([
            'association' => 3,
            'fieldName' => 'address',
            'name' => 'address',
            'type' => 'one',
            'embedded' => true,
            'targetDocument' => Address::class,
            'collectionClass' => null,
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ], $classMetadata->fieldMappings['address']);

        self::assertEquals([
            'association' => 4,
            'fieldName' => 'phonenumbers',
            'name' => 'phonenumbers',
            'type' => 'many',
            'embedded' => true,
            'targetDocument' => Phonenumber::class,
            'collectionClass' => null,
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
        ], $classMetadata->fieldMappings['phonenumbers']);

        self::assertEquals([
            'association' => 1,
            'fieldName' => 'profile',
            'name' => 'profile',
            'type' => 'one',
            'reference' => true,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID,
            'targetDocument' => Profile::class,
            'collectionClass' => null,
            'cascade' => ['remove', 'persist', 'refresh', 'merge', 'detach'],
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => true,
            'prime' => [],
        ], $classMetadata->fieldMappings['profile']);

        self::assertEquals([
            'association' => 1,
            'fieldName' => 'account',
            'name' => 'account',
            'type' => 'one',
            'reference' => true,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'targetDocument' => Account::class,
            'collectionClass' => null,
            'cascade' => ['remove', 'persist', 'refresh', 'merge', 'detach'],
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
            'prime' => [],
        ], $classMetadata->fieldMappings['account']);

        self::assertEquals([
            'association' => 2,
            'fieldName' => 'groups',
            'name' => 'groups',
            'type' => 'many',
            'reference' => true,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'targetDocument' => Group::class,
            'collectionClass' => null,
            'cascade' => ['remove', 'persist', 'refresh', 'merge', 'detach'],
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
            'prime' => [],
        ], $classMetadata->fieldMappings['groups']);

        self::assertEquals(
            [
                'postPersist' => ['doStuffOnPostPersist', 'doOtherStuffOnPostPersist'],
                'prePersist' => ['doStuffOnPrePersist'],
            ],
            $classMetadata->lifecycleCallbacks,
        );

        self::assertEquals(
            [
                'doStuffOnAlsoLoad' => ['unmappedField'],
            ],
            $classMetadata->alsoLoadMethods,
        );

        $classMetadata = new ClassMetadata(EmbeddedDocument::class);
        $this->driver->loadMetadataForClass(EmbeddedDocument::class, $classMetadata);

        self::assertEquals([
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
        ], $classMetadata->fieldMappings['name']);

        $classMetadata = new ClassMetadata(QueryResultDocument::class);
        $this->driver->loadMetadataForClass(QueryResultDocument::class, $classMetadata);

        self::assertEquals([
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
        ], $classMetadata->fieldMappings['name']);

        self::assertEquals([
            'fieldName' => 'count',
            'name' => 'count',
            'type' => 'int',
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ], $classMetadata->fieldMappings['count']);
    }

    public function testPartialFilterExpressions(): void
    {
        $classMetadata = new ClassMetadata(PartialFilterDocument::class);
        $this->driver->loadMetadataForClass(PartialFilterDocument::class, $classMetadata);

        self::assertEquals([
            [
                'keys' => ['fieldA' => 1],
                'options' => [
                    'partialFilterExpression' => [
                        'version' => ['$gt' => 1],
                        'discr' => ['$eq' => 'default'],
                        'parent' => ['$eq' => null],
                    ],
                ],
            ],
            [
                'keys' => ['fieldB' => 1],
                'options' => [
                    'partialFilterExpression' => [
                        '$and' => [
                            ['version' => ['$gt' => 1]],
                            ['discr' => ['$eq' => 'default']],
                        ],
                    ],
                ],
            ],
            [
                'keys' => ['fieldC' => 1],
                'options' => [
                    'partialFilterExpression' => [
                        'embedded' => ['foo' => 'bar'],
                    ],
                ],
            ],
        ], $classMetadata->getIndexes());
    }

    public function testCollectionPrimers(): void
    {
        $classMetadata = new ClassMetadata(PrimedCollectionDocument::class);
        $this->driver->loadMetadataForClass(PrimedCollectionDocument::class, $classMetadata);

        self::assertEquals([
            'association' => 2,
            'fieldName' => 'references',
            'name' => 'references',
            'type' => 'many',
            'reference' => true,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'targetDocument' => PrimedCollectionDocument::class,
            'collectionClass' => null,
            'cascade' => [],
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
            'prime' => [],
        ], $classMetadata->fieldMappings['references']);

        self::assertEquals([
            'association' => 2,
            'fieldName' => 'inverseMappedBy',
            'name' => 'inverseMappedBy',
            'type' => 'many',
            'reference' => true,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'targetDocument' => PrimedCollectionDocument::class,
            'collectionClass' => null,
            'cascade' => [],
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => true,
            'isOwningSide' => false,
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
            'inversedBy' => null,
            'mappedBy' => 'references',
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
            'prime' => ['references'],
        ], $classMetadata->fieldMappings['inverseMappedBy']);
    }

    public function testNullableFieldsMapping(): void
    {
        $classMetadata = new ClassMetadata(NullableFieldsDocument::class);
        $this->driver->loadMetadataForClass(NullableFieldsDocument::class, $classMetadata);

        self::assertEquals([
            'fieldName' => 'username',
            'name' => 'username',
            'type' => 'string',
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ], $classMetadata->fieldMappings['username']);

        self::assertEquals([
            'association' => ClassMetadata::EMBED_ONE,
            'fieldName' => 'address',
            'name' => 'address',
            'type' => ClassMetadata::ONE,
            'embedded' => true,
            'targetDocument' => Address::class,
            'collectionClass' => null,
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
        ], $classMetadata->fieldMappings['address']);

        self::assertEquals([
            'association' => ClassMetadata::EMBED_MANY,
            'fieldName' => 'phonenumbers',
            'name' => 'phonenumbers',
            'type' => ClassMetadata::MANY,
            'embedded' => true,
            'targetDocument' => Phonenumber::class,
            'collectionClass' => null,
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
        ], $classMetadata->fieldMappings['phonenumbers']);

        self::assertEquals([
            'association' => ClassMetadata::REFERENCE_ONE,
            'fieldName' => 'profile',
            'name' => 'profile',
            'type' => ClassMetadata::ONE,
            'reference' => true,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'targetDocument' => Profile::class,
            'collectionClass' => null,
            'cascade' => [],
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
            'prime' => [],
        ], $classMetadata->fieldMappings['profile']);

        self::assertEquals([
            'association' => ClassMetadata::REFERENCE_MANY,
            'fieldName' => 'groups',
            'name' => 'groups',
            'type' => ClassMetadata::MANY,
            'reference' => true,
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'targetDocument' => Group::class,
            'collectionClass' => null,
            'cascade' => [],
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => true,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_PUSH_ALL,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
            'prime' => [],
        ], $classMetadata->fieldMappings['groups']);
    }
}
