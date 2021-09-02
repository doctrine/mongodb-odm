<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use TestDocuments\AlsoLoadDocument;
use TestDocuments\CustomIdGenerator;
use TestDocuments\InvalidPartialFilterDocument;
use TestDocuments\SchemaInvalidDocument;
use TestDocuments\SchemaValidatedDocument;
use TestDocuments\UserCustomIdGenerator;
use TestDocuments\UserNonStringOptions;

use function MongoDB\BSON\fromJSON;
use function MongoDB\BSON\toPHP;

class XmlDriverTest extends AbstractDriverTest
{
    public function setUp(): void
    {
        $this->driver = new XmlDriver(__DIR__ . '/fixtures/xml');
    }

    public function testDriverShouldReturnOptionsForCustomIdGenerator(): void
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

    public function testDriverShouldParseNonStringAttributes(): void
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

    public function testInvalidPartialFilterExpressions(): void
    {
        $classMetadata = new ClassMetadata(InvalidPartialFilterDocument::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches('#The mapping file .+ is invalid#');

        $this->driver->loadMetadataForClass(InvalidPartialFilterDocument::class, $classMetadata);
    }

    public function testAlsoLoadFieldMapping(): void
    {
        $classMetadata = new ClassMetadata(AlsoLoadDocument::class);
        $this->driver->loadMetadataForClass(AlsoLoadDocument::class, $classMetadata);

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
            'nullable' => false,
            'strategy' => ClassMetadata::STORAGE_STRATEGY_SET,
            'also-load' => 'createdOn,creation_date',
            'alsoLoadFields' => ['createdOn', 'creation_date'],
        ], $classMetadata->fieldMappings['createdAt']);
    }

    public function testValidationMapping(): void
    {
        $classMetadata = new ClassMetadata(SchemaValidatedDocument::class);
        $this->driver->loadMetadataForClass($classMetadata->name, $classMetadata);
        $this->assertEquals(ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN, $classMetadata->getValidationAction());
        $this->assertEquals(ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE, $classMetadata->getValidationLevel());
        $expectedValidatorJson = <<<'EOT'
{
    "$jsonSchema": {
        "required": ["name"],
        "properties": {
            "name": {
                "bsonType": "string",
                "description": "must be a string and is required"
            }
        }
    },
    "$or": [
        { "phone": { "$type": "string" } },
        { "email": { "$regex": { "$regularExpression" : { "pattern": "@mongodb\\.com$", "options": "" } } } },
        { "status": { "$in": [ "Unknown", "Incomplete" ] } }
    ]
}
EOT;
        $expectedValidatorBson = fromJSON($expectedValidatorJson);
        $expectedValidator     = toPHP($expectedValidatorBson, []);
        $this->assertEquals($expectedValidator, $classMetadata->getValidator());
    }

    public function testWrongValueForValidationSchemaShouldThrowException(): void
    {
        $classMetadata = new ClassMetadata(SchemaInvalidDocument::class);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The following schema validation error occurred while parsing the "schema-validation" property of the "TestDocuments\SchemaInvalidDocument" class: "Got parse error at "w", position 13: "SPECIAL_EXPECTED"" (code 0).');
        $this->driver->loadMetadataForClass($classMetadata->name, $classMetadata);
    }
}

namespace TestDocuments;

use Doctrine\Common\Collections\Collection;
use Documents\Group;
use Documents\Profile;

class UserCustomIdGenerator
{
    /** @var string|null */
    protected $id;
}

class CustomIdGenerator
{
    /** @var string|null */
    protected $id;
}

class UserNonStringOptions
{
    /** @var string|null */
    protected $id;

    /** @var Profile|null */
    protected $profile;

    /** @var Collection<int, Group> */
    protected $groups;
}
