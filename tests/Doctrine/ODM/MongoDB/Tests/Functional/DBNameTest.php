<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Account, 
    Documents\User,
    Documents\SpecialUser;

class DbNameTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testPrefixDbName()
    {
        $this->dm->getConfiguration()->setDatabasePrefix('test_');

        $meta = $this->getMetaData();

        $expectedDbName = 'test_doctrine_odm_tests';

        $this->assertEquals($meta['account']->getDatabase(),     $expectedDbName);
        $this->assertEquals($meta['user']->getDatabase(),        $expectedDbName);
        $this->assertEquals($meta['specialUser']->getDatabase(), $expectedDbName);
    }

    public function testSuffixDbName()
    {
        $this->dm->getConfiguration()->setDatabaseSuffix('_test');

        $meta = $this->getMetaData();

        $expectedDbName = 'doctrine_odm_tests_test';

        $this->assertEquals($meta['account']->getDatabase(),     $expectedDbName);
        $this->assertEquals($meta['user']->getDatabase(),        $expectedDbName);
        $this->assertEquals($meta['specialUser']->getDatabase(), $expectedDbName);
    }

    public function testPrefixAndSuffixDbName()
    {
        $this->dm->getConfiguration()->setDatabasePrefix('test_');
        $this->dm->getConfiguration()->setDatabaseSuffix('_test');

        $meta = $this->getMetaData();

        $expectedDbName = 'test_doctrine_odm_tests_test';

        $this->assertEquals($meta['account']->getDatabase(),     $expectedDbName);
        $this->assertEquals($meta['user']->getDatabase(),        $expectedDbName);
        $this->assertEquals($meta['specialUser']->getDatabase(), $expectedDbName);
    }

    public function testPersist()
    {
        $this->dm->getConfiguration()->setDatabasePrefix('test_');

        $account = new Account();
        $account->setName('test');

        $this->dm->persist($account);
        $this->dm->flush();

        $this->assertTrue(!is_null($account->getId()));

        $conn = $this->dm->getConnection();

        $testAccount = $conn->selectDatabase('test_doctrine_odm_tests')
            ->selectCollection('accounts')
            ->findOne(array('_id' => new \MongoId($account->getId())));

        $this->assertTrue(is_array($testAccount));
        $this->assertEquals($account->getId(), (string)$testAccount['_id']);
    }

    protected function getMetaData()
    {
        return array(
            'account'     => $this->dm->getClassMetadata('Documents\Account'),
            'user'        => $this->dm->getClassMetadata('Documents\User'),
            'specialUser' => $this->dm->getClassMetadata('Documents\SpecialUser')
        );
    }
}
