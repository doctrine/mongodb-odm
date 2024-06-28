<?php

declare(strict_types=1);

namespace Documentation\Validation;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

#[ODM\Document]
#[ODM\Validation(
    validator: self::VALIDATOR,
    action: ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR,
    level: ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT,
)]
class SchemaValidated
{
    private const VALIDATOR = <<<'EOT'
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

    #[ODM\Id]
    public string $id;

    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\Field(type: 'string')]
    public string $phone;

    #[ODM\Field(type: 'string')]
    public string $email;

    #[ODM\Field(type: 'string')]
    public string $status;
}
