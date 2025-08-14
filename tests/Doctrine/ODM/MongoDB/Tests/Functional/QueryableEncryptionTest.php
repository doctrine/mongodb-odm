<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Encryption\Patient;
use Documents\Encryption\PatientBilling;
use Documents\Encryption\PatientRecord;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Regex;
use MongoDB\Client;
use MongoDB\Model\BSONDocument;

use function getenv;
use function iterator_to_array;
use function random_bytes;

class QueryableEncryptionTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->skipTestIfQueryableEncryptionNotSupported();
    }

    public function tearDown(): void
    {
        $this->dm?->getDocumentCollection(Patient::class)?->drop(['encryptedFields' => []]);

        parent::tearDown();
    }

    public function testCreateAndQueryEncryptedCollection(): void
    {
        $nonEncryptedClient   = new Client(self::getUri());
        $nonEncryptedDatabase = $nonEncryptedClient->getDatabase(DOCTRINE_MONGODB_DATABASE);

        // Create the encrypted collection
        $this->dm->getSchemaManager()->createDocumentCollection(Patient::class);

        // Test created collections
        $collectionNames = iterator_to_array($nonEncryptedDatabase->listCollectionNames());
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

        // Data is encrypted
        $document = $nonEncryptedDatabase->getCollection('patients')->findOne(['patientName' => 'Jon Doe']);
        self::assertInstanceOf(BSONDocument::class, $document);
        self::assertSame('Jon Doe', $document->patientName);
        self::assertSame(12345678, $document->patientId);
        self::assertInstanceOf(Binary::class, $document->patientRecord->ssn);
        self::assertSame(Binary::TYPE_ENCRYPTED, $document->patientRecord->ssn->getType());
        self::assertInstanceOf(Binary::class, $document->patientRecord->billing);
        self::assertSame(Binary::TYPE_ENCRYPTED, $document->patientRecord->billing->getType());
        self::assertInstanceOf(Binary::class, $document->patientRecord->billingAmount);
        self::assertSame(Binary::TYPE_ENCRYPTED, $document->patientRecord->billingAmount->getType());

        // Queryable with equality
        $result = $this->dm->getRepository(Patient::class)->findOneBy(['patientRecord.ssn' => '987-65-4320']);
        self::assertNotNull($result);
        self::assertSame('Jon Doe', $result->patientName);
        self::assertSame('987-65-4320', $result->patientRecord->ssn);
        self::assertSame('4111111111111111', $result->patientRecord->billing->number);

        $this->dm->clear();

        // Queryable with range
        $result = $this->dm->getRepository(Patient::class)->findOneBy(['patientRecord.billingAmount' => ['$gt' => 1000, '$lt' => 2000]]);
        self::assertNotNull($result);
        self::assertSame('Jon Doe', $result->patientName);
        self::assertSame('987-65-4320', $result->patientRecord->ssn);
        self::assertSame('4111111111111111', $result->patientRecord->billing->number);

        // Drop the encrypted collection
        $this->dm->getSchemaManager()->dropDocumentCollection(Patient::class);
        $collectionNames = iterator_to_array($nonEncryptedDatabase->listCollectionNames(['filter' => ['name' => new Regex('patients')]]));
        self::assertSame([], $collectionNames, 'The 2 metadata collections should also be dropped');
    }

    protected static function createTestDocumentManager(): DocumentManager
    {
        $config = static::getConfiguration();
        $config->setDefaultDB(DOCTRINE_MONGODB_DATABASE);
        $config->setKmsProvider([
            'type' => 'local',
            'key' => new Binary(random_bytes(96)),
        ]);

        $autoEncryptionOptions = [];

        $cryptSharedLibPath = getenv('CRYPT_SHARED_LIB_PATH');
        if ($cryptSharedLibPath) {
            $autoEncryptionOptions['extraOptions']['cryptSharedLibPath'] = $cryptSharedLibPath;
        }

        $config->setAutoEncryption($autoEncryptionOptions);

        $client = new Client(self::getUri(), [], $config->getDriverOptions());

        return DocumentManager::create($client, $config);
    }
}
