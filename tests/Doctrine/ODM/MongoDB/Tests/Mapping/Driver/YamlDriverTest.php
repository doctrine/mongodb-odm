<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

require_once __DIR__ . '/../../../../../../TestInit.php';
require_once 'fixtures/User.php';
require_once 'fixtures/EmbeddedDocument.php';

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class YamlDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateYmlDriver()
    {
        $xmlDriver = new YamlDriver(
            __DIR__ . '/fixtures/yaml'
        );
        $classMetadata = new ClassMetadata('TestDocuments\User');
        $xmlDriver->loadMetadataForClass('TestDocuments\User', $classMetadata);
        $this->assertEquals(array(
            'fieldName'        => 'id',
            'id'               => true,
            'name'             => 'id',
            'type'             => 'id',
            'isCascadeDetach'  => '',
            'isCascadeMerge'   => '',
            'isCascadePersist' => '',
            'isCascadeRefresh' => '',
            'isCascadeRemove'  => '',
            'nullable'         => false
        ), $classMetadata->fieldMappings['id']);

        $this->assertEquals(array(
            'fieldName'        => 'username',
            'name'             => 'username',
            'type'             => 'string',
            'isCascadeDetach'  => '',
            'isCascadeMerge'   => '',
            'isCascadePersist' => '',
            'isCascadeRefresh' => '',
            'isCascadeRemove'  => '',
            'nullable'         => false
        ), $classMetadata->fieldMappings['username']);

        $this->assertEquals(array(
            'fieldName'        => 'createdAt',
            'name'             => 'createdAt',
            'type'             => 'date',
            'isCascadeDetach'  => '',
            'isCascadeMerge'   => '',
            'isCascadePersist' => '',
            'isCascadeRefresh' => '',
            'isCascadeRemove'  => '',
            'nullable'         => false
        ), $classMetadata->fieldMappings['createdAt']);

        $this->assertEquals(array(
            'fieldName'        => 'address',
            'name'             => 'address',
            'type'             => 'one',
            'embedded'         => true,
            'targetDocument'   => 'Documents\Address',
            'isCascadeDetach'  => '',
            'isCascadeMerge'   => '',
            'isCascadePersist' => '',
            'isCascadeRefresh' => '',
            'isCascadeRemove'  => '',
            'nullable'         => false
        ), $classMetadata->fieldMappings['address']);

        $this->assertEquals(array(
            'fieldName'        => 'phonenumbers',
            'name'             => 'phonenumbers',
            'type'             => 'many',
            'embedded'         => true,
            'targetDocument'   => 'Documents\Phonenumber',
            'isCascadeDetach'  => '',
            'isCascadeMerge'   => '',
            'isCascadePersist' => '',
            'isCascadeRefresh' => '',
            'isCascadeRemove'  => '',
            'nullable'         => false
        ), $classMetadata->fieldMappings['phonenumbers']);

        $this->assertEquals(array(
            'cascade'          => 'all',
            'fieldName'        => 'profile',
            'name'             => 'profile',
            'type'             => 'one',
            'reference'        => true,
            'targetDocument'   => 'Documents\Profile',
            'isCascadeDetach'  => true,
            'isCascadeMerge'   => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove'  => true,
            'nullable'         => false
        ), $classMetadata->fieldMappings['profile']);

        $this->assertEquals(array(
            'cascade'          => 'all',
            'fieldName'        => 'account',
            'name'             => 'account',
            'type'             => 'one',
            'reference'        => true,
            'targetDocument'   => 'Documents\Account',
            'isCascadeDetach'  => true,
            'isCascadeMerge'   => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove'  => true,
            'nullable'         => false
        ), $classMetadata->fieldMappings['account']);

        $this->assertEquals(array(
            'cascade'          => 'all',
            'fieldName'        => 'groups',
            'name'             => 'groups',
            'type'             => 'many',
            'reference'        => true,
            'targetDocument'   => 'Documents\Group',
            'isCascadeDetach'  => true,
            'isCascadeMerge'   => true,
            'isCascadePersist' => true,
            'isCascadeRefresh' => true,
            'isCascadeRemove'  => true,
            'nullable'         => false
        ), $classMetadata->fieldMappings['groups']);

        $this->assertEquals(array(
            'postPersist' => array('doStuffOnPostPersist', 'doOtherStuffOnPostPersist'),
            'prePersist' => array('doStuffOnPrePersist')
          ),
          $classMetadata->lifecycleCallbacks
        );

        $classMetadata = new ClassMetadata('TestDocuments\EmbeddedDocument');
        $xmlDriver->loadMetadataForClass('TestDocuments\EmbeddedDocument', $classMetadata);

        $this->assertEquals(array(
            'fieldName'        => 'name',
            'name'             => 'name',
            'type'             => 'string',
            'isCascadeDetach'  => false,
            'isCascadeMerge'   => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove'  => false,
            'nullable'         => false
        ), $classMetadata->fieldMappings['name']);
    }
}