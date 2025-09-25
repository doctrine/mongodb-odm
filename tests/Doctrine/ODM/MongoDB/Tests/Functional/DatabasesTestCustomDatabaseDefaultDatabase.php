<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class DatabasesTest extends BaseTestCase
{
    public function testCustomDatabase(): void
    {
        self::assertEquals('test_custom', $this->dm->getDocumentDatabase(TestCustomDatabase::class)->getDatabaseName());
    }

    public function testDefaultDatabase(): void
    {
        self::assertEquals('test_default', $this->dm->getDocumentDatabase(TestDefaultDatabase::class)->getDatabaseName());
    }

    protected static function getConfiguration(): Configuration
    {
        $config = parent::getConfiguration();

        $config->setDefaultDB('test_default');

        return $config;
    }
}

#[ODM\Document(db: 'test_custom')]
class TestCustomDatabase
{
    /** @var string|null */
    #[ODM\Id]
    private $id;
}

#[ODM\Document]
class TestDefaultDatabase
{
    /** @var string|null */
    #[ODM\Id]
    private $id;
}
