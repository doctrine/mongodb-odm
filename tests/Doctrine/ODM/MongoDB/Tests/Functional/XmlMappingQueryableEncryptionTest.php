<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class XmlMappingQueryableEncryptionTest extends QueryableEncryptionTest
{
    protected static function createMetadataDriverImpl(): MappingDriver
    {
        return new XmlDriver(__DIR__ . '/../Mapping/Driver/fixtures/xml');
    }
}
