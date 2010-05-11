<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

require_once 'TestInit.php';
require_once 'fixtures/User.php';

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class YamlDriverTest extends \PHPUnit_Framework_TestCase {

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
            'type'             => 'string',
            'isCascadeDetach'  => '',
            'isCascadeMerge'   => '',
            'isCascadePersist' => '',
            'isCascadeRefresh' => '',
            'isCascadeRemove'  => '',
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
        ), $classMetadata->fieldMappings['groups']);
	}

}
