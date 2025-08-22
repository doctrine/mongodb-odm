<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Utility\EncryptedFieldsMapGenerator;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\ReflectionService;
use Documents\Bars\Bar;
use Documents\Encryption\Client;
use Documents\Encryption\InvalidRootEncrypt;
use Documents\Encryption\Patient;
use Documents\Encryption\PatientRecord;
use Documents\Encryption\RangeTypes;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Int64;
use MongoDB\BSON\UTCDateTime;

use function array_map;

use const PHP_INT_MAX;

class EncryptedFieldsMapGeneratorTest extends BaseTestCase
{
    public function testGetEncryptionFieldsMapForClass(): void
    {
        $factory            = new EncryptedFieldsMapGenerator($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptedFieldsForClass(Patient::class);

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

        self::assertEquals(['fields' => $expected], $encryptedFieldsMap);
    }

    public function testGetEncryptionFieldsForClassWithEmbeddedDocument(): void
    {
        $factory            = new EncryptedFieldsMapGenerator($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptedFieldsForClass(Client::class);

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

        self::assertEquals(['fields' => $expected], $encryptedFieldsMap);
    }

    public function testVariousRangeTypes(): void
    {
        $factory            = new EncryptedFieldsMapGenerator($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptedFieldsForClass(RangeTypes::class);

        $expected = [
            [
                'path' => 'intField',
                'bsonType' => 'int',
                'keyId' => null,
                'queries' => ['queryType' => 'range', 'min' => 5, 'max' => 10],
            ],
            [
                'path' => 'int64Field',
                'bsonType' => 'long',
                'keyId' => null,
                'queries' => [
                    'queryType' => 'range',
                    'min' => new Int64(5),
                    'max' => new Int64(PHP_INT_MAX - 5),
                ],
            ],
            [
                'path' => 'floatField',
                'bsonType' => 'double',
                'keyId' => null,
                'queries' => ['queryType' => 'range', 'min' => 5.5, 'max' => 10.5, 'precision' => 1],
            ],
            [
                'path' => 'decimalField',
                'bsonType' => 'decimal',
                'keyId' => null,
                'queries' => ['queryType' => 'range', 'min' => new Decimal128('0.1'), 'max' => new Decimal128('0.2'), 'precision' => 2],
            ],
            [
                'path' => 'dateField',
                'bsonType' => 'date',
                'keyId' => null,
                'queries' => [
                    'queryType' => 'range',
                    'min' => new UTCDateTime(new DateTimeImmutable('2000-01-01 00:00:00')),
                    'max' => new UTCDateTime(new DateTimeImmutable('2100-01-01 00:00:00')),
                    'sparsity' => 1,
                    'trimFactor' => 3,
                    'contention' => 4,
                ],
            ],
        ];

        self::assertEquals(['fields' => $expected], $encryptedFieldsMap);
    }

    public function testRootDocumentsCannotBeEncrypted(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The root document class "Documents\Encryption\InvalidRootEncrypt" cannot be encrypted. Only fields and embedded documents can be encrypted.');

        $factory = new EncryptedFieldsMapGenerator($this->dm->getMetadataFactory());
        $factory->getEncryptedFieldsForClass(InvalidRootEncrypt::class);
    }

    public function testGetEncryptionFieldsMap(): void
    {
        $classMetadataFactory = $this->createMetadataFactory(
            $this->dm->getMetadataFactory(),
            Patient::class,
            PatientRecord::class,
        );

        $factory            = new EncryptedFieldsMapGenerator($classMetadataFactory);
        $encryptedFieldsMap = $factory->getEncryptedFieldsMap();

        $expectedEncryptedFieldsMap = [
            Patient::class => [
                'fields' => [
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
                ],
            ],
        ];

        $this->assertEquals($expectedEncryptedFieldsMap, $encryptedFieldsMap);
    }

    public function testNoEncryptedFields(): void
    {
        $classMetadataFactory = $this->createMetadataFactory(
            $this->dm->getMetadataFactory(),
            Bar::class,
        );

        $factory = new EncryptedFieldsMapGenerator($classMetadataFactory);

        self::assertSame([], $factory->getEncryptedFieldsMap());
        self::assertNull($factory->getEncryptedFieldsForClass(Bar::class));
    }

    public function testNotADocumentClass(): void
    {
        $classMetadataFactory = $this->createMetadataFactory(
            $this->dm->getMetadataFactory(),
            PatientRecord::class,
        );

        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('The class "Documents\Encryption\PatientRecord" is not a document class.');

        $factory = new EncryptedFieldsMapGenerator($classMetadataFactory);
        $factory->getEncryptedFieldsForClass(PatientRecord::class);
    }

    private function createMetadataFactory(ClassMetadataFactoryInterface $classMetadataFactory, string ...$className): ClassMetadataFactoryInterface
    {
        return new class ($classMetadataFactory, $className) extends AbstractClassMetadataFactory implements ClassMetadataFactoryInterface
        {
            public function __construct(private ClassMetadataFactoryInterface $classMetadataFactory, private array $classNames)
            {
            }

            public function getAllMetadata(): array
            {
                return array_map(
                    $this->classMetadataFactory->getMetadataFor(...),
                    $this->classNames,
                );
            }

            public function getMetadataFor(string $className): ClassMetadata
            {
                return $this->classMetadataFactory->getMetadataFor($className);
            }

            protected function initialize(): void
            {
            }

            protected function getDriver(): MappingDriver
            {
                return $this->classMetadataFactory->getDriver();
            }

            protected function wakeupReflection(\Doctrine\Persistence\Mapping\ClassMetadata $class, ReflectionService $reflService): void
            {
                $this->classMetadataFactory->wakeupReflection($class, $reflService);
            }

            protected function initializeReflection(\Doctrine\Persistence\Mapping\ClassMetadata $class, ReflectionService $reflService): void
            {
                $this->classMetadataFactory->initializeReflection($class, $reflService);
            }

            protected function isEntity(\Doctrine\Persistence\Mapping\ClassMetadata $class): bool
            {
                return $this->classMetadataFactory->isEntity($class);
            }

            protected function doLoadMetadata(\Doctrine\Persistence\Mapping\ClassMetadata $class, ?\Doctrine\Persistence\Mapping\ClassMetadata $parent, bool $rootEntityFound, array $nonSuperclassParents): void
            {
                $this->classMetadataFactory->doLoadMetadata($class, $parent, $rootEntityFound, $nonSuperclassParents);
            }

            protected function newClassMetadataInstance(string $className): ClassMetadata
            {
                return $this->classMetadataFactory->newClassMetadataInstance($className);
            }

            public function setConfiguration(Configuration $config): void
            {
            }

            public function setDocumentManager(DocumentManager $dm): void
            {
            }
        };
    }
}
