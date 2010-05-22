<?php

require_once 'TestInit.php';

use Documents\Account, 
    Documents\User,
    Documents\SpecialUser;

class DbNameTest extends BaseTest
{
    public function testPrefixDbName()
    {
        $this->dm->getConfiguration()->setDBPrefix('test_');

        $meta = $this->getMetaData();

        $expectedDbName = 'test_doctrine_odm_tests';

        $this->assertEquals($meta['account']->getDB(),     $expectedDbName);
        $this->assertEquals($meta['user']->getDB(),        $expectedDbName);
        $this->assertEquals($meta['specialUser']->getDB(), $expectedDbName);
    }

    public function testSuffixDbName()
    {
        $this->dm->getConfiguration()->setDBSuffix('_test');

        $meta = $this->getMetaData();

        $expectedDbName = 'doctrine_odm_tests_test';

        $this->assertEquals($meta['account']->getDB(),     $expectedDbName);
        $this->assertEquals($meta['user']->getDB(),        $expectedDbName);
        $this->assertEquals($meta['specialUser']->getDB(), $expectedDbName);
    }

    public function testPrefixAndSuffixDbName()
    {
        $this->dm->getConfiguration()->setDBPrefix('test_');
        $this->dm->getConfiguration()->setDBSuffix('_test');

        $meta = $this->getMetaData();

        $expectedDbName = 'test_doctrine_odm_tests_test';

        $this->assertEquals($meta['account']->getDB(),     $expectedDbName);
        $this->assertEquals($meta['user']->getDB(),        $expectedDbName);
        $this->assertEquals($meta['specialUser']->getDB(), $expectedDbName);
    }

    public function testPersist()
    {
        $this->dm->getConfiguration()->setDBPrefix('test_');

        $account = new Account();
        $account->setName('test');

        $this->dm->persist($account);
        $this->dm->flush();

        $this->assertTrue(!is_null($account->getId()));

        $mongo = $this->dm->getMongo()->getMongo();

        $testAccount = $mongo->test_doctrine_odm_tests
            ->accounts
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
