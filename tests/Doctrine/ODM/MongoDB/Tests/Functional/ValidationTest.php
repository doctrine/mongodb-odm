<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\SchemaValidated;

use function MongoDB\BSON\fromJSON;
use function MongoDB\BSON\fromPHP;
use function MongoDB\BSON\toCanonicalExtendedJSON;
use function MongoDB\BSON\toPHP;

class ValidationTest extends BaseTest
{
    public function testCreateUpdateValidatedDocument()
    {
        $this->requireVersion($this->getServerVersion(), '3.6.0', '<', 'MongoDB cannot perform JSON schema validation before version 3.6');

        // Test creation of SchemaValidated collection
        $cm = $this->dm->getClassMetadata(SchemaValidated::class);
        $this->dm->getSchemaManager()->createDocumentCollection($cm->name);
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
        $expectedOptions       = [
            'validator' => $expectedValidator,
            'validationLevel' => ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
            'validationAction' => ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
        ];
        $expectedOptionsBson   = fromPHP($expectedOptions);
        $expectedOptionsJson   = toCanonicalExtendedJSON($expectedOptionsBson);
        $collections           = $this->dm->getDocumentDatabase($cm->name)->listCollections();
        $assertNb              = 0;
        foreach ($collections as $collection) {
            if ($collection->getName() !== $cm->getCollection()) {
                continue;
            }

            $assertNb++;
            $collectionOptionsBson = fromPHP($collection->getOptions());
            $collectionOptionsJson = toCanonicalExtendedJSON($collectionOptionsBson);
            $this->assertJsonStringEqualsJsonString($expectedOptionsJson, $collectionOptionsJson);
        }

        $this->assertEquals(1, $assertNb);

        // Test updating the same collection, this time removing the validators and resetting to default options
        $cmUpdated = $this->dm->getClassMetadata(SchemaValidatedUpdate::class);
        $this->dm->getSchemaManager()->updateDocumentValidator($cmUpdated->name);
        // We expect the default values set by MongoDB
        // See: https://docs.mongodb.com/manual/reference/command/collMod/#document-validation
        $expectedOptions = [
            'validationLevel' => ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT,
            'validationAction' => ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR,
        ];
        $collections     = $this->dm->getDocumentDatabase($cmUpdated->name)->listCollections();
        $assertNb        = 0;
        foreach ($collections as $collection) {
            if ($collection->getName() !== $cm->getCollection()) {
                continue;
            }

            $assertNb++;
            $this->assertEquals($expectedOptions, $collection->getOptions());
        }

        $this->assertEquals(1, $assertNb);
    }
}

/**
 * @ODM\Document(collection="SchemaValidated")
 */
class SchemaValidatedUpdate extends SchemaValidated
{
}
