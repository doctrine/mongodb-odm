<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Exception;

use function sprintf;

final class ConfigurationException extends Exception
{
    public static function persistentCollectionDirMissing(): self
    {
        return new self('Cannot instantiate a PersistentCollectionGenerator. Please set a target directory first!');
    }

    public static function persistentCollectionNamespaceMissing(): self
    {
        return new self('Cannot instantiate a PersistentCollectionGenerator. Please set a namespace first!');
    }

    public static function noMetadataDriverConfigured(): self
    {
        return new self('No metadata driver was configured. Please set a metadata driver implementation in your configuration.');
    }

    public static function proxyDirMissing(): self
    {
        return new self('No proxy directory was configured. Please set a target directory first!');
    }

    public static function clientEncryptionOptionsNotSet(): self
    {
        return new self('MongoDB client encryption options are not set in configuration');
    }

    public static function kmsProviderTypeRequired(): self
    {
        return new self('The KMS provider "type" is required');
    }

    public static function kmsProviderTypeMustBeString(): self
    {
        return new self('The KMS provider "type" must be a non-empty string');
    }

    public static function kmsProvidersOptionMustUseSetter(): self
    {
        return new self('The "kmsProviders" encryption option must be set using the "setKmsProvider()" method');
    }

    public static function masterKeyRequired(string $provider): self
    {
        return new self(sprintf('The "masterKey" configuration is required for the KMS provider "%s"', $provider));
    }
}
