<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

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

        $test = $this->dm->find(__NAMESPACE__.'\CustomFieldName', $test->id);
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

        $test = $this->dm->find(__NAMESPACE__.'\CustomFieldName', $test->id);

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

        $test = $this->dm->getRepository(__NAMESPACE__.'\CustomFieldName')->findOneBy(array('username' => 'test'));
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

        $test = $this->dm->getRepository(__NAMESPACE__.'\CustomFieldName')->findOneBy(array('username' => 'test'));
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

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\CustomFieldName')->field('username')->equals('test');
        $query = $qb->getQuery();
        $test = $query->getSingleResult();
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);
    }
}

/** @ODM\Document */
class CustomFieldName
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(name="login", type="string") */
    public $username;
}
