<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

class CustomFieldNameTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testInsertSetsLoginInsteadOfUsername()
    {
        $test = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        
        $test = $this->dm->getDocumentCollection(__NAMESPACE__.'\CustomFieldName')->findOne();
        $this->assertTrue(isset($test['login']));
        $this->assertEquals('test', $test['login']);
    }

    public function testHydration()
    {
        $test = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->findOne(__NAMESPACE__.'\CustomFieldName');
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);
    }

    public function testUpdateSetsLoginInsteadOfUsername()
    {
        $test = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->findOne(__NAMESPACE__.'\CustomFieldName');

        $test->username = 'ok';
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(__NAMESPACE__.'\CustomFieldName')->findOne();
        $this->assertTrue(isset($test['login']));
        $this->assertEquals('ok', $test['login']);
    }

    public function testFindOneQueryIsPrepared()
    {
        $test = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->findOne(__NAMESPACE__.'\CustomFieldName', array('username' => 'test'));
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);
    }

    public function testFindQueryIsPrepared()
    {
        $test = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(__NAMESPACE__.'\CustomFieldName', array('username' => 'test'))->getSingleResult();
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);
    }

    public function testQueryBuilderAndDqlArePrepared()
    {
        $test = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQuery(__NAMESPACE__.'\CustomFieldName')->field('username')->equals('test')->getSingleResult();
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);

        $test = $this->dm->query('find all from '.__NAMESPACE__.'\CustomFieldName where username = ?', 'test')->getSingleResult();
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);
    }
}

/** @Document */
class CustomFieldName
{
    /** @Id */
    public $id;

    /** @String(name="login") */
    public $username;
}