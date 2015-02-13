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

    public function testDriverShouldParseNonStringAttributes()
    {
        $classMetadata = new ClassMetadata('TestDocuments\UserNonStringOptions');
        $this->driver->loadMetadataForClass('TestDocuments\UserNonStringOptions', $classMetadata);

        $this->assertSame(true, $classMetadata->requireIndexes);
        $this->assertSame(false, $classMetadata->slaveOkay);

        $profileMapping = $classMetadata->fieldMappings['profile'];
        $this->assertSame(true, $profileMapping['simple']);
        $this->assertSame(true, $profileMapping['orphanRemoval']);

        $profileMapping = $classMetadata->fieldMappings['groups'];
        $this->assertSame(false, $profileMapping['simple']);
        $this->assertSame(false, $profileMapping['orphanRemoval']);
        $this->assertSame(0, $profileMapping['limit']);
        $this->assertSame(2, $profileMapping['skip']);
    }
}

namespace TestDocuments;

class UserCustomIdGenerator
{
    protected $id;
}

class UserNonStringOptions
{
    protected $id;
    protected $profile;
    protected $groups;
}

