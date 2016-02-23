<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Tests\BaseTest;

use Documents\Ecommerce\Currency;
use Documents\Ecommerce\ConfigurableProduct;
use Documents\CmsArticle;
use Documents\CmsComment;
use Documents\Functional\SameCollection1;
use Documents\Functional\SameCollection2;

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

    public function testQueryBuilderUpdateWithDiscriminatorMap()
    {
        $testCollection = new SameCollection1();
        $id = '4f28aa84acee413889000001';

        $testCollection->id = $id;
        $testCollection->name = 'First entry';
        $this->dm->persist($testCollection);
        $this->dm->flush();
        $this->uow->computeChangeSets();

        /**
         * @var \Doctrine\ODM\MongoDB\Query\Builder $qb
         */
        $qb = $this->dm->createQueryBuilder('Documents\Functional\SameCollection1');
        $qb->update()
            ->field('ok')->set(true)
            ->field('test')->set('OK! TEST')
            ->field('id')->equals($id);

        /**
         * @var \Doctrine\ODM\MongoDB\Query\Query $query
         * @var \Doctrine\ODM\MongoDB\Cursor $results
         */
        $query = $qb->getQuery();
        $results = $query->execute();

        $this->dm->refresh($testCollection);

        $this->assertEquals('OK! TEST', $testCollection->test);
    }

    public function testFindWithOrOnCollectionWithDiscriminatorMap()
    {
        $sameCollection1 = new SameCollection1();
        $sameCollection2a = new SameCollection2();
        $sameCollection2b = new SameCollection2();
        $ids = array(
            '4f28aa84acee413889000001',
            '4f28aa84acee41388900002a',
            '4f28aa84acee41388900002b'
        );

        $sameCollection1->id = $ids[0];
        $sameCollection1->name = 'First entry in SameCollection1';
        $sameCollection1->test = 1;
        $this->dm->persist($sameCollection1);
        $this->dm->flush();

        $sameCollection2a->id = $ids[1];
        $sameCollection2a->name = 'First entry in SameCollection2';
        $sameCollection2a->ok = true;
        $this->dm->persist($sameCollection2a);
        $this->dm->flush();

        $sameCollection2b->id = $ids[2];
        $sameCollection2b->name = 'Second entry in SameCollection2';
        $sameCollection2b->w00t = '!!';
        $this->dm->persist($sameCollection2b);
        $this->dm->flush();

        $this->uow->computeChangeSets();

        /**
         * @var \Doctrine\ODM\MongoDB\Query\Builder $qb
         */
        $qb = $this->dm->createQueryBuilder('Documents\Functional\SameCollection2');
        $qb
            ->field('id')->in($ids)
            ->select('id')->hydrate(false);
        /**
         * @var \Doctrine\ODM\MongoDB\Query\Query $query
         * @var \Doctrine\ODM\MongoDB\Cursor $results
         */
        $query = $qb->getQuery();
        $debug = $query->debug('query');
        $results = $query->execute();

        $this->assertInstanceOf('Doctrine\MongoDB\CursorInterface', $results);

        $targetClass = $this->dm->getClassMetadata('Documents\Functional\SameCollection2');
        $mongoId1 = $targetClass->getDatabaseIdentifierValue($ids[1]);

        $this->assertEquals($mongoId1, $debug['_id']['$in'][1]);


        $this->assertEquals(2, $results->count(true));
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
                '$db' => DOCTRINE_MONGODB_DATABASE,
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
                '$db' => DOCTRINE_MONGODB_DATABASE,
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
                    '$db' => DOCTRINE_MONGODB_DATABASE,
                    '$id' => new \MongoId($article->id),
                    '$ref' => 'CmsArticle'
                ),
                '_id' => new \MongoId($comment->id),
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

    public function testAdvancedQueriesOnReferenceWithDiscriminatorMap()
    {
        $article = new CmsArticle();
        $article->id = '4f8373f952fbfe7411000001';
        $article->title = 'advanced queries test';
        $this->dm->persist($article);
        $this->dm->flush();

        $comment = new CmsComment();
        $comment->id = '4f8373f952fbfe7411000002';
        $comment->article = $article;
        $this->dm->persist($comment);
        $this->dm->flush();

        $this->uow->computeChangeSets();

        $articleId = (string) $article->id;
        $commentId = (string) $comment->id;

        /**
         * @var \Doctrine\ODM\MongoDB\Query\Builder $qb
         */
        $qb = $this->dm->createQueryBuilder('Documents\CmsComment');
        $qb
            ->field('article.id')->in(array($articleId));
        /**
         * @var \Doctrine\ODM\MongoDB\Query\Query $query
         * @var \Doctrine\ODM\MongoDB\Cursor $results
         */
        $query = $qb->getQuery();
        $results = $query->execute();

        $this->assertInstanceOf('Doctrine\MongoDB\CursorInterface', $results);

        $singleResult = $results->getSingleResult();
        $this->assertInstanceOf('Documents\CmsComment', $singleResult);

        $this->assertEquals($commentId, $singleResult->id);

        $this->assertInstanceOf('Documents\CmsArticle', $singleResult->article);

        $this->assertEquals($articleId, $singleResult->article->id);
        $this->assertEquals(1, $results->count(true));
    }
}
