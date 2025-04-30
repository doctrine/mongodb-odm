<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Utility\EncryptionFieldMap;
use Documents\Encryption\Client;
use Documents\Encryption\InvalidRootEncrypt;
use Documents\Encryption\Patient;

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

        self::assertSame($expected, $encryptedFieldsMap);
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

    public function testRootDocumentsCannotBeEncrypted(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The root document class "Documents\Encryption\InvalidRootEncrypt" cannot be encrypted. Only fields and embedded documents can be encrypted.');

        $factory = new EncryptionFieldMap($this->dm->getMetadataFactory());
        $factory->getEncryptionFieldMap(InvalidRootEncrypt::class);
    }
}
