<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class DatabasesTest extends BaseTest
{
    public function testCustomDatabase(): void
    {
        $this->assertEquals('test_custom', $this->dm->getDocumentDatabase(CustomDatabaseTest::class)->getDatabaseName());
    }

    public function testDefaultDatabase(): void
    {
        $this->assertEquals('test_default', $this->dm->getDocumentDatabase(DefaultDatabaseTest::class)->getDatabaseName());
    }

    protected function getConfiguration(): Configuration
    {
        $config = parent::getConfiguration();

        $config->setDefaultDB('test_default');

        return $config;
    }
}

/** @ODM\Document(db="test_custom") */
class CustomDatabaseTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;
}

/** @ODM\Document() */
class DefaultDatabaseTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;
}
