<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\Driver\ClientEncryption;

class QueryableEncryptionTest extends BaseTestCase
{
    public function testBasic(): void
    {

    }


    protected static function getConfiguration(): Configuration
    {
        $config = parent::getConfiguration();

        return $config;
    }
}

#[ODM\Document]
class EncryptedDocument
{
    #[ODM\Id]
    public string $id;

    #[ODM\Field]
    #[ODM\Encrypt(queryType: ClientEncryption::QUERY_TYPE_EQUALITY)]
    private string $sensitiveField;
}