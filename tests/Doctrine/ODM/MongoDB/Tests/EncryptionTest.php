<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Utility\EncryptionFieldMap;
use Documents\Encryption\Patient;

class EncryptionTest extends BaseTestCase
{
    public function testMetadataIsEncrypted(): void
    {
        $factory            = new EncryptionFieldMap($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptionFieldMap(Patient::class);

        $expected = [
            [
                'name' => 'patientRecord.ssn',
                'type' => 'string',
                'keyId' => null,
                'queries' => ['queryType' => 'equality'],
            ],
            [
                'name' => 'patientRecord.billing',
                'type' => 'object',
                'keyId' => null,
            ],
            [
                'name' => 'patientRecord.billingAmount',
                'type' => 'int',
                'keyId' => null,
                'queries' => ['queryType' => 'range', 'min' => 100, 'max' => 2000, 'sparsity' => 1, 'trimFactor' => 4],
            ],
        ];

        self::assertSame($expected, $encryptedFieldsMap);
    }
}
