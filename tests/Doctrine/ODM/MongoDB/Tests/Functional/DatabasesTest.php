<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class DatabasesTest extends BaseTest
{
    public function testCustomDatabase()
    {
        $this->assertEquals('test_custom', $this->dm->getDocumentDatabase(CustomDatabaseTest::class)->getDatabaseName());
    }

    public function testDefaultDatabase()
    {
        $this->assertEquals('test_default', $this->dm->getDocumentDatabase(DefaultDatabaseTest::class)->getDatabaseName());
    }

    protected function getConfiguration()
    {
        $config = parent::getConfiguration();

        $config->setDefaultDB('test_default');

        return $config;
    }
}

/** @ODM\Document(db="test_custom") */
class CustomDatabaseTest
{
    /** @ODM\Id */
    private $id;
}

/** @ODM\Document() */
class DefaultDatabaseTest
{
    /** @ODM\Id */
    private $id;
}
