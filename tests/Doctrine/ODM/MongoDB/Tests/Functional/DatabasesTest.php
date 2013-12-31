<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class DatabasesTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCustomDatabase()
    {
        $this->assertEquals('test_custom', $this->dm->getDocumentDatabase(__NAMESPACE__ . '\CustomDatabaseTest')->getName());
    }

    public function testDefaultDatabase()
    {
        $this->assertEquals('test_default', $this->dm->getDocumentDatabase(__NAMESPACE__ . '\DefaultDatabaseTest')->getName());
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
