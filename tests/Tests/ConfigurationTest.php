<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Composer\InstalledVersions;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\ConfigurationException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionGenerator;
use LogicException;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;

use function base64_encode;
use function str_repeat;
use function version_compare;

class ConfigurationTest extends TestCase
{
    #[RequiresPhp('< 8.4')]
    public function testUseNativeLazyObjectBeforePHP84(): void
    {
        $c = new Configuration();

        self::expectException(LogicException::class);
        self::expectExceptionMessage('Native lazy objects require PHP 8.4 or higher.');

        $c->setUseNativeLazyObject(true);
    }

    public function testUseLazyGhostObject(): void
    {
        if (! version_compare(InstalledVersions::getVersion('symfony/var-exporter'), '8', '<')) {
            $this->markTestSkipped('Symfony VarExporter 8 or higher is not installed.');
        }

        $c = new Configuration();

        self::assertFalse($c->isLazyGhostObjectEnabled());
        $c->setUseLazyGhostObject(true);
        self::assertTrue($c->isLazyGhostObjectEnabled());
        $c->setUseLazyGhostObject(false);
        self::assertFalse($c->isLazyGhostObjectEnabled());
    }

    #[RequiresPhp('>= 8.4')]
    public function testUseLazyGhostObjectWithSymfony8(): void
    {
        if (version_compare(InstalledVersions::getVersion('symfony/var-exporter'), '8', '<')) {
            $this->markTestSkipped('Symfony VarExporter 8 or higher is not installed.');
        }

        $c = new Configuration();

        self::expectException(LogicException::class);
        self::expectExceptionMessage('Package "symfony/var-exporter" >= 8.0 does not provide lazy ghost objects, use native lazy objects instead.');

        $c->setUseLazyGhostObject(true);
    }

    public function testNativeLazyObjectDeprecatedByDefault(): void
    {
        $c = new Configuration();

        self::assertFalse($c->isNativeLazyObjectEnabled());
    }

    #[RequiresPhp('>= 8.4')]
    #[TestWith([true])]
    #[TestWith([false])]
    public function testConflictingLazyObjectSettings(bool $flag): void
    {
        $c = new Configuration();
        $c->setUseNativeLazyObject(true);

        self::expectException(LogicException::class);
        self::expectExceptionMessage('Cannot enable or disable LazyGhostObject when native lazy objects are enabled.');

        $c->setUseLazyGhostObject($flag);
    }

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
        $c->setPersistentCollectionDir(__DIR__ . '/../PersistentCollections');
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

    public function testLocalKmsProvider(): void
    {
        $c = new Configuration();
        $c->setKmsProvider(['type' => 'local', 'key' => base64_encode(str_repeat('1', 96))]);
        $c->setAutoEncryption(['extraOptions' => ['mongocryptdURI' => 'mongodb://localhost:27020']]);
        $c->setDefaultDB('default_database');

        self::assertSame('local', $c->getDefaultKmsProvider());
        self::assertNull($c->getDefaultMasterKey());
        self::assertEquals([
            'kmsProviders' => [
                'local' => ['key' => base64_encode(str_repeat('1', 96))],
            ],
            'extraOptions' => ['mongocryptdURI' => 'mongodb://localhost:27020'],
            // Default key vault namespace
            'keyVaultNamespace' => 'default_database.datakeys',
        ], $c->getDriverOptions()['autoEncryption']);
    }

    public function testKmsProvider(): void
    {
        $c = new Configuration();
        $c->setKmsProvider(['type' => 'aws', 'accessKeyId' => 'AKIA', 'secretAccessKey' => 'SECRET']);
        $c->setAutoEncryption(['keyVaultNamespace' => 'keyvault.datakeys']);
        $c->setDefaultMasterKey($masterKey = ['region' => 'us-east-1', 'key' => 'arn:aws:kms:us-east-1:123456789012:key/abcd1234-ab12-cd34-ef56-1234567890ab']);

        self::assertSame('aws', $c->getDefaultKmsProvider());
        self::assertSame($masterKey, $c->getDefaultMasterKey());
        self::assertEquals([
            'kmsProviders' => [
                'aws' => ['accessKeyId' => 'AKIA', 'secretAccessKey' => 'SECRET'],
            ],
            // Key vault namespace from the configuration
            'keyVaultNamespace' => 'keyvault.datakeys',
        ], $c->getDriverOptions()['autoEncryption']);
    }

    public function testEmptyKmsProviderOptions(): void
    {
        $c = new Configuration();
        $c->setKmsProvider(['type' => 'aws']);
        $c->setAutoEncryption(['keyVaultNamespace' => 'keyvault.datakeys']);

        self::assertEquals([
            'kmsProviders' => [
                'aws' => new stdClass(),
            ],
            'keyVaultNamespace' => 'keyvault.datakeys',
        ], $c->getDriverOptions()['autoEncryption']);
    }

    public function testAutoEncryptionOptions(): void
    {
        $c = new Configuration();
        $c->setAutoEncryption([
            'keyVaultClient' => $keyVaultClient = new Manager(),
            'keyVaultNamespace' => 'keyvault.datakeys',
            'extraOptions' => ['mongocryptdURI' => 'mongodb://localhost:27020'],
            'tlsOptions' => ['local' => ['tlsDisableOCSPEndpointCheck' => true]],
        ]);
        $c->setKmsProvider(['type' => 'local', 'key' => '1234567890123456789012345678901234567890123456789012345678901234']);

        self::assertEquals([
            'kmsProviders' => [
                'local' => ['key' => '1234567890123456789012345678901234567890123456789012345678901234'],
            ],
            'keyVaultNamespace' => 'keyvault.datakeys',
            'keyVaultClient' => $keyVaultClient,
            'extraOptions' => ['mongocryptdURI' => 'mongodb://localhost:27020'],
            'tlsOptions' => ['local' => ['tlsDisableOCSPEndpointCheck' => true]],
        ], $c->getDriverOptions()['autoEncryption']);

        self::assertEquals([
            'kmsProviders' => [
                'local' => ['key' => '1234567890123456789012345678901234567890123456789012345678901234'],
            ],
            'keyVaultNamespace' => 'keyvault.datakeys',
            'keyVaultClient' => $keyVaultClient,
            'tlsOptions' => ['local' => ['tlsDisableOCSPEndpointCheck' => true]],
        ], $c->getClientEncryptionOptions());
    }

    public function testMissingDefaultMasterKey(): void
    {
        $c = new Configuration();
        $c->setKmsProvider(['type' => 'aws', 'accessKeyId' => 'AKIA', 'secretAccessKey' => 'SECRET']);

        self::expectException(ConfigurationException::class);
        self::expectExceptionMessage('The "masterKey" configuration is required for the KMS provider "aws"');
        $c->getDefaultMasterKey();
    }

    public function testKmsProvidersIsForbiddenInAutoEncryptionOptions(): void
    {
        $c = new Configuration();

        self::expectException(ConfigurationException::class);
        self::expectExceptionMessage('The "kmsProviders" encryption option must be set using the "setKmsProvider()" method');
        $c->setAutoEncryption(['kmsProviders' => ['aws' => ['accessKeyId' => 'AKIA', 'secretAccessKey' => 'SECRET']]]);
    }

    public function testClientEncryptionOptionsNotSet(): void
    {
        $c = new Configuration();
        self::expectException(ConfigurationException::class);
        self::expectExceptionMessage('MongoDB client encryption options are not set in configuration');
        $c->getClientEncryptionOptions();
    }

    public function testKmsProviderTypeRequired(): void
    {
        $c = new Configuration();
        self::expectException(ConfigurationException::class);
        self::expectExceptionMessage('The KMS provider "type" is required');

        // @phpstan-ignore argument.type
        $c->setKmsProvider(['foo' => 'bar']);
    }

    public function testKmsProviderTypeMustBeString(): void
    {
        $c = new Configuration();
        self::expectException(ConfigurationException::class);
        self::expectExceptionMessage('The KMS provider "type" must be a non-empty string');

        // @phpstan-ignore argument.type
        $c->setKmsProvider(['type' => ['not', 'a', 'string']]);
    }
}
