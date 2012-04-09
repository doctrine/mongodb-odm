<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Tests\BaseTest;

use Documents\Ecommerce\Currency;
use Documents\Ecommerce\ConfigurableProduct;
use Documents\CmsArticle;
use Documents\CmsComment;

class PersistenceBuilderTest extends BaseTest
{
    private $pb;

    public function setUp()
    {
        parent::setUp();
        $this->pb = $this->dm->getUnitOfWork()->getPersistenceBuilder();
    }

    public function tearDown()
    {
        unset($this->pb);
        parent::tearDown();
    }

    public function testPrepareUpdateDataDoesNotIncludeId()
    {
        $article = new CmsArticle();
        $article->topic = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $this->dm->getRepository(get_class($article))->find($article->id);
        $article->id = null;
        $article->topic = 'test';

        $this->uow->computeChangeSets();
        $data = $this->pb->prepareUpdateData($article);
        $this->assertFalse(isset($data['$unset']['_id']));
    }

    public function testFindWithOrOnCollectionWithDiscriminatorMap()
    {
        $id = '4f28aa84acee41388900000a';
        $ids = array($id);

        /**
         * @var \Doctrine\ODM\MongoDB\Query\Builder $qb
         */
        $qb = $this->dm->createQueryBuilder('Documents\Functional\SameCollection1');
        $qb
            ->addOr($qb->expr()->field('id')->in($ids))
            ->select('id')->hydrate(false);
        /**
         * @var \Doctrine\ODM\MongoDB\Query\Query $query
         * @var \Doctrine\ODM\MongoDB\Cursor $results
         */
        $query = $qb->getQuery();
        $results = $query->execute();
        $debug = $query->debug();

        $this->assertEquals($id, (string) current($debug['$or'][0]['_id']['$in']));

        $this->assertInstanceOf('Doctrine\MongoDB\Cursor', $results);
        $this->assertEquals(0, $query->count());
    }

    public function testPrepareInsertDataWithCreatedReferenceOne()
    {
        $article = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $comment = new CmsComment();
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = array(
            'article' => array(
                '$db' => 'doctrine_odm_tests',
                '$id' => new \MongoId($article->id),
                '$ref' => 'CmsArticle'
            )
        );
        $this->assertDocumentInsertData($expectedData, $this->pb->prepareInsertData($comment));
    }

    public function testPrepareInsertDataWithFetchedReferenceOne()
    {
        $article = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $this->dm->find(get_class($article), $article->id);
        $comment = new CmsComment();
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = array(
            'article' => array(
                '$db' => 'doctrine_odm_tests',
                '$id' => new \MongoId($article->id),
                '$ref' => 'CmsArticle'
            )
        );
        $this->assertDocumentInsertData($expectedData, $this->pb->prepareInsertData($comment));
    }

    public function testPrepareUpsertData()
    {
        $article = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $this->dm->find(get_class($article), $article->id);
        $comment = new CmsComment();
        $comment->topic = 'test';
        $comment->text = 'text';
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = array(
            '$set' => array(
                'topic' => 'test',
                'text' => 'text',
                'article' => array(
                    '$db' => 'doctrine_odm_tests',
                    '$id' => new \MongoId($article->id),
                    '$ref' => 'CmsArticle'
                )
            )
        );
        $this->assertEquals($expectedData, $this->pb->prepareUpsertData($comment));
    }

    /**
     * @dataProvider getDocumentsAndExpectedData
     */
    public function testPrepareInsertData($document, array $expectedData)
    {
        $this->dm->persist($document);
        $this->uow->computeChangeSets();
        $this->assertDocumentInsertData($expectedData, $this->pb->prepareInsertData($document));
    }

    /**
     * Provides data for @see PersistenceBuilderTest::testPrepareInsertData()
     * Returns arrays of array(document => expected data)
     *
     * @return array
     */
    public function getDocumentsAndExpectedData()
    {
        return array(
            array(new ConfigurableProduct('Test Product'), array('name' => 'Test Product')),
            array(new Currency('USD', 1), array('name' => 'USD', 'multiplier' => 1)),
        );
    }

    private function assertDocumentInsertData(array $expectedData, array $preparedData = null)
    {
        foreach ($preparedData as $key => $value) {
            if ($key === '_id') {
                $this->assertInstanceOf('MongoId', $value);
                continue;
            }
            $this->assertEquals($expectedData[$key], $value);
        }
        if ( ! isset($preparedData['_id'])) {
            $this->fail('insert data should always contain id');
        }
        unset($preparedData['_id']);
        $this->assertEquals(array_keys($expectedData), array_keys($preparedData));
    }
}
