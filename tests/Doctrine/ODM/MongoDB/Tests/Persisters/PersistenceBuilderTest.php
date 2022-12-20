<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsArticle;
use Documents\CmsComment;
use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\Currency;
use Documents\Functional\SameCollection1;
use Documents\Functional\SameCollection2;
use MongoDB\BSON\ObjectId;

use function array_keys;
use function get_class;

class PersistenceBuilderTest extends BaseTest
{
    private PersistenceBuilder $pb;

    public function setUp(): void
    {
        parent::setUp();

        $this->pb = $this->dm->getUnitOfWork()->getPersistenceBuilder();
    }

    public function tearDown(): void
    {
        unset($this->pb);

        parent::tearDown();
    }

    public function testQueryBuilderUpdateWithDiscriminatorMap(): void
    {
        $testCollection = new SameCollection1();
        $id             = '4f28aa84acee413889000001';

        $testCollection->id   = $id;
        $testCollection->name = 'First entry';
        $this->dm->persist($testCollection);
        $this->dm->flush();
        $this->uow->computeChangeSets();

        $qb = $this->dm->createQueryBuilder(SameCollection1::class);
        $qb->updateOne()
            ->field('ok')->set(true)
            ->field('test')->set('OK! TEST')
            ->field('id')->equals($id);

        $query = $qb->getQuery();
        $query->execute();

        $this->dm->refresh($testCollection);

        self::assertEquals('OK! TEST', $testCollection->test);
    }

    public function testFindWithOrOnCollectionWithDiscriminatorMap(): void
    {
        $sameCollection1  = new SameCollection1();
        $sameCollection2a = new SameCollection2();
        $sameCollection2b = new SameCollection2();
        $ids              = [
            '4f28aa84acee413889000001',
            '4f28aa84acee41388900002a',
            '4f28aa84acee41388900002b',
        ];

        $sameCollection1->id   = $ids[0];
        $sameCollection1->name = 'First entry in SameCollection1';
        $sameCollection1->test = 'test';
        $this->dm->persist($sameCollection1);
        $this->dm->flush();

        $sameCollection2a->id   = $ids[1];
        $sameCollection2a->name = 'First entry in SameCollection2';
        $sameCollection2a->ok   = true;
        $this->dm->persist($sameCollection2a);
        $this->dm->flush();

        $sameCollection2b->id   = $ids[2];
        $sameCollection2b->name = 'Second entry in SameCollection2';
        $sameCollection2b->w00t = '!!';
        $this->dm->persist($sameCollection2b);
        $this->dm->flush();

        $this->uow->computeChangeSets();

        $qb = $this->dm->createQueryBuilder(SameCollection2::class);
        $qb
            ->field('id')->in($ids)
            ->select('id')->hydrate(false);
        $query   = $qb->getQuery();
        $debug   = $query->debug('query');
        $results = $query->execute();

        self::assertInstanceOf(Iterator::class, $results);

        $targetClass = $this->dm->getClassMetadata(SameCollection2::class);
        $identifier1 = $targetClass->getDatabaseIdentifierValue($ids[1]);

        self::assertEquals($identifier1, $debug['_id']['$in'][1]);

        self::assertCount(2, $results->toArray());
    }

    public function testPrepareUpdateDataDoesNotIncludeId(): void
    {
        $article        = new CmsArticle();
        $article->topic = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article        = $this->dm->getRepository(get_class($article))->find($article->id);
        $article->id    = null;
        $article->topic = 'test';

        $this->uow->computeChangeSets();
        $data = $this->pb->prepareUpdateData($article);
        self::assertFalse(isset($data['$unset']['_id']));
    }

    public function testPrepareInsertDataWithCreatedReferenceOne(): void
    {
        $article        = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $comment          = new CmsComment();
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = [
            'article' => [
                '$id' => new ObjectId($article->id),
                '$ref' => 'CmsArticle',
            ],
            'nullableField' => null,
        ];
        $this->assertDocumentInsertData($expectedData, $this->pb->prepareInsertData($comment));
    }

    public function testPrepareInsertDataWithFetchedReferenceOne(): void
    {
        $article        = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article          = $this->dm->find(get_class($article), $article->id);
        $comment          = new CmsComment();
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = [
            'article' => [
                '$id' => new ObjectId($article->id),
                '$ref' => 'CmsArticle',
            ],
            'nullableField' => null,
        ];
        $this->assertDocumentInsertData($expectedData, $this->pb->prepareInsertData($comment));
    }

    public function testPrepareUpsertData(): void
    {
        $article        = new CmsArticle();
        $article->title = 'persistence builder test';
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article          = $this->dm->find(get_class($article), $article->id);
        $comment          = new CmsComment();
        $comment->topic   = 'test';
        $comment->text    = 'text';
        $comment->article = $article;

        $this->dm->persist($comment);
        $this->uow->computeChangeSets();

        $expectedData = [
            '$set' => [
                'topic' => 'test',
                'text' => 'text',
                'article' => [
                    '$id' => new ObjectId($article->id),
                    '$ref' => 'CmsArticle',
                ],
                '_id' => new ObjectId($comment->id),
            ],
            '$setOnInsert' => ['nullableField' => null],
        ];
        self::assertEquals($expectedData, $this->pb->prepareUpsertData($comment));
    }

    /**
     * @param array<string, mixed> $expectedData
     *
     * @dataProvider getDocumentsAndExpectedData
     */
    public function testPrepareInsertData(object $document, array $expectedData): void
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
    public function getDocumentsAndExpectedData(): array
    {
        return [
            [new ConfigurableProduct('Test Product'), ['name' => 'Test Product']],
            [new Currency('USD', 1), ['name' => 'USD', 'multiplier' => 1]],
        ];
    }

    /**
     * @param array<string, mixed> $expectedData
     * @param array<string, mixed> $preparedData
     */
    private function assertDocumentInsertData(array $expectedData, array $preparedData): void
    {
        foreach ($preparedData as $key => $value) {
            if ($key === '_id') {
                self::assertInstanceOf(ObjectId::class, $value);
                continue;
            }

            self::assertEquals($expectedData[$key], $value);
        }

        if (! isset($preparedData['_id'])) {
            $this->fail('insert data should always contain id');
        }

        unset($preparedData['_id']);
        self::assertEquals(array_keys($expectedData), array_keys($preparedData));
    }

    public function testAdvancedQueriesOnReferenceWithDiscriminatorMap(): void
    {
        $article        = new CmsArticle();
        $article->id    = '4f8373f952fbfe7411000001';
        $article->title = 'advanced queries test';
        $this->dm->persist($article);
        $this->dm->flush();

        $comment          = new CmsComment();
        $comment->id      = '4f8373f952fbfe7411000002';
        $comment->article = $article;
        $this->dm->persist($comment);
        $this->dm->flush();

        $this->uow->computeChangeSets();

        $articleId = $article->id;
        $commentId = $comment->id;

        $qb = $this->dm->createQueryBuilder(CmsComment::class);
        $qb
            ->field('article.id')->in([$articleId]);
        $query   = $qb->getQuery();
        $results = $query->execute();

        self::assertInstanceOf(Iterator::class, $results);

        $singleResult = $query->getSingleResult();
        self::assertInstanceOf(CmsComment::class, $singleResult);

        self::assertEquals($commentId, $singleResult->id);

        self::assertInstanceOf(CmsArticle::class, $singleResult->article);

        self::assertEquals($articleId, $singleResult->article->id);
        self::assertCount(1, $results->toArray());
    }
}
