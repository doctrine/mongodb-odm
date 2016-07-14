<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

require_once 'fixtures/InvalidPartialFilterDocument.php';
require_once 'fixtures/PartialFilterDocument.php';
require_once 'fixtures/User.php';
require_once 'fixtures/EmbeddedDocument.php';

abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase
{
    protected $driver;

    public function setUp()
    {
        // implement driver setup and metadata read
    }

    public function tearDown()
    {
        unset ($this->driver);
    }

    public function testDriver()
    {

        $classMetadata = new ClassMetadata('TestDocuments\User');
        $this->driver->loadMetadataForClass('TestDocuments\User', $classMetadata);

        $this->assertEquals(array(
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
            'nullable' => false
        ), $classMetadata->fieldMappings['id']);

        $this->assertEquals(array(
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
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_SET,
        ), $classMetadata->fieldMappings['username']);
        
        $this->assertEquals(array(
            array(
                'keys' => array('username' => 1),
                'options' => array('unique' => true, 'sparse' => true)
            )
        ), $classMetadata->getIndexes());

        $this->assertEquals(array(
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
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_SET,
        ), $classMetadata->fieldMappings['createdAt']);

        $this->assertEquals(array(
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
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_SET,
        ), $classMetadata->fieldMappings['tags']);

        $this->assertEquals(array(
            'association' => 3,
            'fieldName' => 'address',
            'name' => 'address',
            'type' => 'one',
            'embedded' => true,
            'targetDocument' => 'Documents\Address',
            'collectionClass' => null,
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_SET,
        ), $classMetadata->fieldMappings['address']);

        $this->assertEquals(array(
            'association' => 4,
            'fieldName' => 'phonenumbers',
            'name' => 'phonenumbers',
            'type' => 'many',
            'embedded' => true,
            'targetDocument' => 'Documents\Phonenumber',
            'collectionClass' => null,
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_PUSH_ALL,
        ), $classMetadata->fieldMappings['phonenumbers']);

        $this->assertEquals(array(
            'association' => 1,
            'fieldName' => 'profile',
            'name' => 'profile',
            'type' => 'one',
            'reference' => true,
            'storeAs' => ClassMetadataInfo::REFERENCE_STORE_AS_ID,
            'simple' => true,
            'targetDocument' => 'Documents\Profile',
            'collectionClass' => null,
            'cascade' => array('remove', 'persist', 'refresh', 'merge', 'detach'),
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_SET,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => true,
        ), $classMetadata->fieldMappings['profile']);

        $this->assertEquals(array(
            'association' => 1,
            'fieldName' => 'account',
            'name' => 'account',
            'type' => 'one',
            'reference' => true,
            'storeAs' => ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF_WITH_DB,
            'simple' => false,
            'targetDocument' => 'Documents\Account',
            'collectionClass' => null,
            'cascade' => array('remove', 'persist', 'refresh', 'merge', 'detach'),
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_SET,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
        ), $classMetadata->fieldMappings['account']);

        $this->assertEquals(array(
            'association' => 2,
            'fieldName' => 'groups',
            'name' => 'groups',
            'type' => 'many',
            'reference' => true,
            'storeAs' => ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF_WITH_DB,
            'simple' => false,
            'targetDocument' => 'Documents\Group',
            'collectionClass' => null,
            'cascade' => array('remove', 'persist', 'refresh', 'merge', 'detach'),
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_PUSH_ALL,
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
        ), $classMetadata->fieldMappings['groups']);

        $this->assertEquals(
            array(
                'postPersist' => array('doStuffOnPostPersist', 'doOtherStuffOnPostPersist'),
                'prePersist' => array('doStuffOnPrePersist'),
            ),
            $classMetadata->lifecycleCallbacks
        );

        $this->assertEquals(
            array(
                "doStuffOnAlsoLoad" => array("unmappedField"),
            ),
            $classMetadata->alsoLoadMethods
        );

        $classMetadata = new ClassMetadata('TestDocuments\EmbeddedDocument');
        $this->driver->loadMetadataForClass('TestDocuments\EmbeddedDocument', $classMetadata);

        $this->assertEquals(array(
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
            'strategy' => ClassMetadataInfo::STORAGE_STRATEGY_SET,
        ), $classMetadata->fieldMappings['name']);
    }

    public function testPartialFilterExpressions()
    {
        $classMetadata = new ClassMetadata('TestDocuments\PartialFilterDocument');
        $this->driver->loadMetadataForClass('TestDocuments\PartialFilterDocument', $classMetadata);

        $this->assertEquals([
            [
                'keys' => ['fieldA' => 1],
                'options' => [
                    'partialFilterExpression' => [
                        'version' => ['$gt' => 1],
                        'discr' => ['$eq' => 'default'],
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
}
