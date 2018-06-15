<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use TestDocuments\CustomIdGenerator;
use TestDocuments\InvalidPartialFilterDocument;
use TestDocuments\UserCustomIdGenerator;
use TestDocuments\UserNonStringOptions;

class XmlDriverTest extends AbstractDriverTest
{
    public function setUp()
    {
        $this->driver = new XmlDriver(__DIR__ . '/fixtures/xml');
    }

    public function testDriverShouldReturnOptionsForCustomIdGenerator()
    {
        $classMetadata = new ClassMetadata(UserCustomIdGenerator::class);
        $this->driver->loadMetadataForClass(UserCustomIdGenerator::class, $classMetadata);
        $this->assertEquals([
            'fieldName' => 'id',
            'strategy' => 'custom',
            'options' => [
                'class' => CustomIdGenerator::class,
                'someOption' => 'some-option',
            ],
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
            'nullable' => false,
        ], $classMetadata->fieldMappings['id']);
    }

    public function testDriverShouldParseNonStringAttributes()
    {
        $classMetadata = new ClassMetadata(UserNonStringOptions::class);
        $this->driver->loadMetadataForClass(UserNonStringOptions::class, $classMetadata);

        $profileMapping = $classMetadata->fieldMappings['profile'];
        $this->assertSame(ClassMetadata::REFERENCE_STORE_AS_ID, $profileMapping['storeAs']);
        $this->assertTrue($profileMapping['orphanRemoval']);

        $profileMapping = $classMetadata->fieldMappings['groups'];
        $this->assertSame(ClassMetadata::REFERENCE_STORE_AS_DB_REF, $profileMapping['storeAs']);
        $this->assertFalse($profileMapping['orphanRemoval']);
        $this->assertSame(0, $profileMapping['limit']);
        $this->assertSame(2, $profileMapping['skip']);
    }

    public function testInvalidPartialFilterExpressions()
    {
        $classMetadata = new ClassMetadata(InvalidPartialFilterDocument::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageRegExp('#The mapping file .+ is invalid#');

        $this->driver->loadMetadataForClass(InvalidPartialFilterDocument::class, $classMetadata);
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
