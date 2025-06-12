<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Utility\EncryptedFieldsMapGenerator;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\ReflectionService;
use Documents\Encryption\Client;
use Documents\Encryption\InvalidRootEncrypt;
use Documents\Encryption\Patient;
use Documents\Encryption\PatientRecord;
use Documents\Encryption\RangeTypes;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;

use function array_map;

class EncryptedFieldsMapGeneratorTest extends BaseTestCase
{
    public function testGetEncryptionFieldsMapForClass(): void
    {
        $factory            = new EncryptedFieldsMapGenerator($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptedFieldsMapForClass(Patient::class);

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

    public function testGetEncryptionFieldsMapForClassForEmbeddedDocument(): void
    {
        $factory            = new EncryptedFieldsMapGenerator($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptedFieldsMapForClass(Client::class);

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
        $factory            = new EncryptedFieldsMapGenerator($this->dm->getMetadataFactory());
        $encryptedFieldsMap = $factory->getEncryptedFieldsMapForClass(RangeTypes::class);

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

        $factory = new EncryptedFieldsMapGenerator($this->dm->getMetadataFactory());
        $factory->getEncryptedFieldsMapForClass(InvalidRootEncrypt::class);
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
        ];

        $this->assertEquals($expectedEncryptedFieldsMap, $encryptedFieldsMap);
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
