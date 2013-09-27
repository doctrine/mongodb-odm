<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class XmlDriverTest extends AbstractDriverTest
{
    public function setUp()
    {
        $this->driver = new XmlDriver(__DIR__ . '/fixtures/xml');
    }
    
    public function testDriverShouldReturnOptionsForCustomIdGenerator()
    {
        $classMetadata = new ClassMetadata('TestDocuments\UserCustomIdGenerator');
        $this->driver->loadMetadataForClass('TestDocuments\UserCustomIdGenerator', $classMetadata);
        $this->assertEquals(array(
            'fieldName' => 'id',
            'strategy' => 'custom',
            'options' => array(
                'class' => 'TestDocuments\CustomIdGenerator',
                'someOption' => 'some-option'
            ),
            'id' => true,
            'name' => '_id',
            'type' => 'custom_id',
            'isCascadeCallbacks' => false,
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false
        ), $classMetadata->fieldMappings['id']);
    }
}

namespace TestDocuments;

class UserCustomIdGenerator
{
    protected $id;

    public function __construct()
    {
    }
}

