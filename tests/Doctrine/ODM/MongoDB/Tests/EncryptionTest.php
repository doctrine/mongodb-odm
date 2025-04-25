<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Utility\EncryptionFieldMap;
use Documents\Encryption\Patient;

class EncryptionTest extends BaseTestCase
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
}
