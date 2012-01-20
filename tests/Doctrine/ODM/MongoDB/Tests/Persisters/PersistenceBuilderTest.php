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
