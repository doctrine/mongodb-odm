<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @ODM\Document
 * @ODM\Validation(
 *     validator=SchemaValidated::VALIDATOR,
 *     action=ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
 *     level=ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
 * )
 */
class SchemaValidated
{
    public const VALIDATOR = <<<'EOT'
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
        { "email": { "$regularExpression" : { "pattern": "@mongodb\\.com$", "options": "" } } },
        { "status": { "$in": [ "Unknown", "Incomplete" ] } }
    ]
}
EOT;

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $phone;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $email;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $status;
}
