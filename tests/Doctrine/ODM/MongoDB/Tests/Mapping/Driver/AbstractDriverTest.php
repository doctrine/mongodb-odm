<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

require_once 'fixtures/User.php';
require_once 'fixtures/EmbeddedDocument.php';

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
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

        $this->assertEquals([
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
        ], $classMetadata->fieldMappings['id']);

        $this->assertEquals([
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
            'sparse' => true
        ], $classMetadata->fieldMappings['username']);
        
        $this->assertEquals([
            [
                'keys' => ['username' => 1],
                'options' => ['unique' => true, 'sparse' => true]
            ]
        ], $classMetadata->getIndexes());

        $this->assertEquals([
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
            'nullable' => false
        ], $classMetadata->fieldMappings['createdAt']);

        $this->assertEquals([
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
        ], $classMetadata->fieldMappings['tags']);

        $this->assertEquals([
            'association' => 3,
            'fieldName' => 'address',
            'name' => 'address',
            'type' => 'one',
            'embedded' => true,
            'targetDocument' => 'Documents\Address',
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => 'pushAll',
        ], $classMetadata->fieldMappings['address']);

        $this->assertEquals([
            'association' => 4,
            'fieldName' => 'phonenumbers',
            'name' => 'phonenumbers',
            'type' => 'many',
            'embedded' => true,
            'targetDocument' => 'Documents\Phonenumber',
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => 'pushAll',
        ], $classMetadata->fieldMappings['phonenumbers']);

        $this->assertEquals([
            'association' => 1,
            'fieldName' => 'profile',
            'name' => 'profile',
            'type' => 'one',
            'reference' => true,
            'simple' => true,
            'targetDocument' => 'Documents\Profile',
            'cascade' => ['remove', 'persist', 'refresh', 'merge', 'detach'],
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => 'pushAll',
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => true,
        ], $classMetadata->fieldMappings['profile']);

        $this->assertEquals([
            'association' => 1,
            'fieldName' => 'account',
            'name' => 'account',
            'type' => 'one',
            'reference' => true,
            'simple' => false,
            'targetDocument' => 'Documents\Account',
            'cascade' => ['remove', 'persist', 'refresh', 'merge', 'detach'],
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => 'pushAll',
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
        ], $classMetadata->fieldMappings['account']);

        $this->assertEquals([
            'association' => 2,
            'fieldName' => 'groups',
            'name' => 'groups',
            'type' => 'many',
            'reference' => true,
            'simple' => false,
            'targetDocument' => 'Documents\Group',
            'cascade' => ['remove', 'persist', 'refresh', 'merge', 'detach'],
            'isCascadeDetach' => true,
            'isCascadeMerge' => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove' => true,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false,
            'strategy' => 'pushAll',
            'inversedBy' => null,
            'mappedBy' => null,
            'repositoryMethod' => null,
            'limit' => null,
            'skip' => null,
            'orphanRemoval' => false,
        ], $classMetadata->fieldMappings['groups']);

        $this->assertEquals(
            [
                'postPersist' => ['doStuffOnPostPersist', 'doOtherStuffOnPostPersist'],
                'prePersist' => ['doStuffOnPrePersist'],
            ],
            $classMetadata->lifecycleCallbacks
        );

        $this->assertEquals(
            [
                "doStuffOnAlsoLoad" => ["unmappedField"],
            ],
            $classMetadata->alsoLoadMethods
        );

        $classMetadata = new ClassMetadata('TestDocuments\EmbeddedDocument');
        $this->driver->loadMetadataForClass('TestDocuments\EmbeddedDocument', $classMetadata);

        $this->assertEquals([
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
        ], $classMetadata->fieldMappings['name']);
    }

}
