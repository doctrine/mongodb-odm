<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Exception;

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
}
