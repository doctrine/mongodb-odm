<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionGenerator;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class ConfigurationTest extends BaseTestCase
{
    public function testDefaultPersistentCollectionFactory(): void
    {
        $c       = new Configuration();
        $factory = $c->getPersistentCollectionFactory();
        self::assertInstanceOf(PersistentCollectionFactory::class, $factory);
        self::assertSame($factory, $c->getPersistentCollectionFactory());
    }

    public function testDefaultPersistentCollectionGenerator(): void
    {
        $c = new Configuration();
        $c->setPersistentCollectionDir(__DIR__ . '/../../../../PersistentCollections');
        $c->setPersistentCollectionNamespace('PersistentCollections');
        $generator = $c->getPersistentCollectionGenerator();
        self::assertInstanceOf(PersistentCollectionGenerator::class, $generator);
        self::assertSame($generator, $c->getPersistentCollectionGenerator());
    }

    public function testEnableTransactionalFlush(): void
    {
        $c = new Configuration();

        self::assertFalse($c->isTransactionalFlushEnabled(), 'Transactional flush is disabled by default');

        $c->setUseTransactionalFlush(true);
        self::assertTrue($c->isTransactionalFlushEnabled(), 'Transactional flush is enabled after setTransactionalFlush(true)');

        $c->setUseTransactionalFlush(false);
        self::assertFalse($c->isTransactionalFlushEnabled(), 'Transactional flush is disabled after setTransactionalFlush(false)');
    }

    public function testAutoEncryptionWithMultipleKmsProviders(): void
    {
        $masterKey = ['region' => 'us-east-1', 'key' => 'arn:aws:kms:us-east-1:123456789012:key/abcd1234-ab12-cd34-ef56-1234567890ab'];
        $c         = new Configuration();
        $c->setAutoEncryption([
            'keyVaultNamespace' => 'encryption.__keyVault',
            'kmsProviders' => [
                'azure' => [
                    'tenantId' => 'TENANT_ID',
                    'clientId' => 'CLIENT_ID',
                    'clientSecret' => 'CLIENT_SECRET',
                ],
                'aws' => [
                    'accessKeyId' => 'AKIA',
                    'secretAccessKey' => 'SECRET',
                ],
            ],
            'kmsProvider' => 'aws',
            'masterKey' => $masterKey,
        ]);

        self::assertSame($masterKey, $c->getMasterKey());
        self::assertSame('aws', $c->getKmsProvider());
        self::assertArrayHasKey('kmsProviders', $c->getAutoEncryption());
    }

    public function testAutoEncryptionSingleKmsProvider(): void
    {
        $masterKey = ['region' => 'us-east-1', 'key' => 'arn:aws:kms:us-east-1:123456789012:key/abcd1234-ab12-cd34-ef56-1234567890ab'];
        $c         = new Configuration();
        $c->setAutoEncryption([
            'keyVaultNamespace' => 'encryption.__keyVault',
            'kmsProviders' => [
                'aws' => [
                    'accessKeyId' => 'AKIA',
                    'secretAccessKey' => 'SECRET',
                ],
            ],
            'masterKey' => $masterKey,
        ]);

        self::assertSame($masterKey, $c->getMasterKey());
        self::assertSame('aws', $c->getKmsProvider());
        self::assertArrayHasKey('kmsProviders', $c->getAutoEncryption());
    }

    public function testAutoEncryptionWithLocalKmsProvider(): void
    {
        $c = new Configuration();
        $c->setAutoEncryption([
            'keyVaultNamespace' => 'encryption.__keyVault',
            'kmsProviders' => [
                'local' => [
                    'key' => ['key' => '1234567890123456789012345678901234567890123456789012345678901234'],
                ],
            ],
        ]);

        self::assertNull($c->getMasterKey());
        self::assertSame('local', $c->getKmsProvider());
        self::assertArrayHasKey('kmsProviders', $c->getAutoEncryption());
    }

    /** @phpstan-ignore missingType.iterableValue */
    #[DataProvider('provideInvalidAutoEncryptionConfigurations')]
    public function testAutoEncryptionMissingConfiguration(string $message, array $config): void
    {
        $c = new Configuration();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage($message);

        // @phpstan-ignore argument.type
        $c->setAutoEncryption($config);
    }

    public function provideInvalidAutoEncryptionConfigurations(): Generator
    {
        yield [
            'The "kmsProviders" encryption option is required and must be a non-empty.',
            [
                'keyVaultNamespace' => 'encryption.__keyVault',
                'kmsProviders' => [],
            ],
        ];

        yield [
            'The "keyVaultNamespace" encryption option is required.',
            [
                'kmsProviders' => [
                    'local' => [
                        'key' => ['key' => '1234567890123456789012345678901234567890123456789012345678901234'],
                    ],
                ],
            ],
        ];

        yield [
            'The "masterKey" option is required when the KMS provider is not "local".',
            [
                'keyVaultNamespace' => 'encryption.__keyVault',
                'kmsProviders' => [
                    'aws' => [
                        'accessKeyId' => 'AKIA',
                        'secretAccessKey' => 'SECRET',
                    ],
                ],
            ],
        ];

        yield [
            'The "kmsProvider" encryption option is required when multiple KMS providers are specified.',
            [
                'keyVaultNamespace' => 'encryption.__keyVault',
                'kmsProviders' => [
                    'aws' => [
                        'accessKeyId' => 'AKIA',
                        'secretAccessKey' => 'SECRET',
                    ],
                    'azure' => [
                        'tenantId' => 'TENANT_ID',
                        'clientId' => 'CLIENT_ID',
                        'clientSecret' => 'CLIENT_SECRET',
                    ],
                ],
            ],
        ];
    }
}
