<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class CustomFieldNameTest extends BaseTest
{
    public function testInsertSetsLoginInsteadOfUsername(): void
    {
        $test           = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(CustomFieldName::class)->findOne();
        $this->assertArrayHasKey('login', $test);
        $this->assertEquals('test', $test['login']);
    }

    public function testHydration(): void
    {
        $test           = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(CustomFieldName::class, $test->id);
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);
    }

    public function testUpdateSetsLoginInsteadOfUsername(): void
    {
        $test           = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(CustomFieldName::class, $test->id);

        $test->username = 'ok';
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(CustomFieldName::class)->findOne();
        $this->assertArrayHasKey('login', $test);
        $this->assertEquals('ok', $test['login']);
    }

    public function testFindOneQueryIsPrepared(): void
    {
        $test           = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(CustomFieldName::class)->findOneBy(['username' => 'test']);
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);
    }

    public function testFindQueryIsPrepared(): void
    {
        $test           = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(CustomFieldName::class)->findOneBy(['username' => 'test']);
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->username);
    }

    public function testQueryBuilderAndDqlArePrepared(): void
    {
        $test           = new CustomFieldName();
        $test->username = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $qb    = $this->dm->createQueryBuilder(CustomFieldName::class)->field('username')->equals('test');
        $query = $qb->getQuery();
        $test  = $query->getSingleResult();
        $this->assertInstanceOf(CustomFieldName::class, $test);
        $this->assertEquals('test', $test->username);
    }
}

/** @ODM\Document */
class CustomFieldName
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(name="login", type="string")
     *
     * @var string|null
     */
    public $username;
}
