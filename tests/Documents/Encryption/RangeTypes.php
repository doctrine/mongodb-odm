<?php

declare(strict_types=1);

namespace Documents\Encryption;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Encrypt;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;
use Doctrine\ODM\MongoDB\Mapping\EncryptQuery;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Decimal128;

use const PHP_INT_MAX;

/**
 * Test all supported types for range encrypted queries.
 *
 * @see https://www.mongodb.com/docs/manual/core/queryable-encryption/reference/supported-operations/#supported-and-unsupported-bson-types
 */
#[Document]
class RangeTypes
{
    #[Id]
    public string $id;

    #[Field(type: Type::INT)]
    #[Encrypt(EncryptQuery::Range, min: 5, max: 10)]
    public int $intField;

    #[Field(type: Type::INT64)]
    #[Encrypt(EncryptQuery::Range, min: 5, max: PHP_INT_MAX - 5)]
    public int $int64Field;

    #[Field(type: Type::FLOAT)]
    #[Encrypt(EncryptQuery::Range, min: 5.5, max: 10.5, precision: 1)]
    public float $floatField;

    #[Field(type: Type::DECIMAL128)]
    #[Encrypt(EncryptQuery::Range, min: new Decimal128('0.1'), max: new Decimal128('0.2'), precision: 2)]
    public Decimal128 $decimalField;

    #[Field(type: Type::DATE_IMMUTABLE)]
    #[Encrypt(
        queryType: EncryptQuery::Range,
        min: new DateTimeImmutable('2000-01-01 00:00:00'),
        max: new DateTimeImmutable('2100-01-01 00:00:00'),
        sparsity: 1,
        trimFactor: 3,
        contention: 4,
    )]
    public DateTimeImmutable $dateField;
}
