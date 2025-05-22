<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Utility\EncryptionFieldMap;
use Documents\Encryption\Client;
use Documents\Encryption\InvalidRootEncrypt;
use Documents\Encryption\Patient;
use Documents\Encryption\RangeTypes;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;

class EncryptionFieldMapTest extends BaseTestCase
{
    public function testEncryptionFieldMap(): void
    {
        $factory            = new EncryptionFieldMap($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptionFieldMap(Patient::class);

        $expected = [
            [
                'path' => 'patientRecord.ssn',
                'bsonType' => 'string',
                'keyId' => null,
                'queries' => ['queryType' => 'equality'],
            ],
            [
                'path' => 'patientRecord.billing',
                'bsonType' => 'object',
                'keyId' => null,
            ],
            [
                'path' => 'patientRecord.billingAmount',
                'bsonType' => 'int',
                'keyId' => null,
                'queries' => ['queryType' => 'range', 'min' => 100, 'max' => 2000, 'sparsity' => 1, 'trimFactor' => 4],
            ],
        ];

        self::assertEquals($expected, $encryptedFieldsMap);
    }

    public function testEncryptEmbeddedDocument(): void
    {
        $factory            = new EncryptionFieldMap($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptionFieldMap(Client::class);

        $expected = [
            [
                'path' => 'name',
                'bsonType' => 'string',
                'keyId' => null,
            ],
            [
                'path' => 'clientCards',
                'bsonType' => 'array',
                'keyId' => null,
            ],
        ];

        self::assertSame($expected, $encryptedFieldsMap);
    }

    public function testVariousRangeTypes(): void
    {
        $factory            = new EncryptionFieldMap($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptionFieldMap(RangeTypes::class);

        $expected = [
            [
                'path' => 'intField',
                'bsonType' => 'int',
                'keyId' => null,
                'queries' => ['queryType' => 'range', 'min' => 5, 'max' => 10],
            ],
            [
                'path' => 'floatField',
                'bsonType' => 'float',
                'keyId' => null,
                'queries' => ['queryType' => 'range', 'min' => 5.5, 'max' => 10.5],
            ],
            [
                'path' => 'decimalField',
                'bsonType' => 'decimal128',
                'keyId' => null,
                'queries' => ['queryType' => 'range', 'min' => new Decimal128('0.1'), 'max' => new Decimal128('0.2')],
            ],
            [
                'path' => 'dateField',
                'bsonType' => 'date_immutable',
                'keyId' => null,
                'queries' => [
                    'queryType' => 'range',
                    'min' => new UTCDateTime(new DateTimeImmutable('2000-01-01 00:00:00')),
                    'max' => new UTCDateTime(new DateTimeImmutable('2100-01-01 00:00:00')),
                ],
            ],
        ];

        self::assertEquals($expected, $encryptedFieldsMap);
    }

    public function testRootDocumentsCannotBeEncrypted(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The root document class "Documents\Encryption\InvalidRootEncrypt" cannot be encrypted. Only fields and embedded documents can be encrypted.');

        $factory = new EncryptionFieldMap($this->dm->getMetadataFactory());
        $factory->getEncryptionFieldMap(InvalidRootEncrypt::class);
    }
}
