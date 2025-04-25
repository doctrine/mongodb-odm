<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Encryption\Patient;
use Documents\Encryption\PatientBilling;
use Documents\Encryption\PatientRecord;
use MongoDB\BSON\Binary;
use MongoDB\Client;

use function base64_decode;
use function iterator_to_array;

class QueryableEncryptionTest extends BaseTestCase
{
    private const LOCAL_MASTERKEY = 'quTJGRzz3TS2yrPUzNf9Ajv+rG2cn0buRsWT6i6BTQihznxZkhYKzyagXZZ05+y/FMEV1kpC79reiJSpysytFyEcXXJChjBsH2iTzBK8uWFN2dN7udzYjWvBJmWKbhhm';

    public function testCreateAndQueryEncryptedCollection(): void
    {
        // @todo skip if not using MongoDB < 7, single node or not enterprise
        $client   = new Client(self::getUri());
        $database = $client->getDatabase(DOCTRINE_MONGODB_DATABASE);

        // Create the encrypted collection
        $this->dm->getSchemaManager()->createDocumentCollection(Patient::class);

        // Test created collectionss
        $collectionNames = iterator_to_array($database->listCollectionNames());
        self::assertContains('patients', $collectionNames);
        self::assertContains('datakeys', $collectionNames);

        // Insert a document
        $patient = new Patient(
            patientName: 'Jon Doe',
            patientId: 12345678,
            patientRecord: new PatientRecord(
                ssn: '987-65-4320',
                billing: new PatientBilling(
                    type: 'Visa',
                    number: '4111111111111111',
                ),
                billingAmount: 1200,
            ),
        );

        $this->dm->persist($patient);
        $this->dm->flush();
        $this->dm->clear();

        // Queryable with equality
        $result = $this->dm->getRepository(Patient::class)->findOneBy(['patientRecord.ssn' => '987-65-4320']);
        self::assertNotNull($result);
        self::assertSame('Jon Doe', $result->patientName);
        self::assertSame('987-65-4320', $result->patientRecord->ssn);
        self::assertSame('4111111111111111', $result->patientRecord->billing->number);

        // Queryable with range
        $result = $this->dm->getRepository(Patient::class)->findOneBy(['patientRecord.billingAmount' => ['$gt' => 1000, '$lt' => 2000]]);
        self::assertSame('Jon Doe', $result->patientName);
        self::assertSame('987-65-4320', $result->patientRecord->ssn);
        self::assertSame('4111111111111111', $result->patientRecord->billing->number);
    }

    protected static function createTestDocumentManager(): DocumentManager
    {
        $config = static::getConfiguration();
        $config->setAutoEncryption([
            'keyVaultNamespace' => DOCTRINE_MONGODB_DATABASE . '.datakeys',
            'kmsProviders' => [
                'local' => ['key' => new Binary(base64_decode(self::LOCAL_MASTERKEY))],
            ],
        ]);

        $client = new Client(self::getUri(), [], ['autoEncryption' => $config->getAutoEncryption()]);

        return DocumentManager::create($client, $config);
    }
}
