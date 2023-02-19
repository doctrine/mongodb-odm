<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Article;

class MODM88Test extends BaseTestCase
{
    public function testTest(): void
    {
        $article = new Article();
        $article->setTitle('Test Title');
        $article->setBody('Test Body');
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $qb       = $this->dm->createQueryBuilder(Article::class)
            ->select('_id', 'title');
        $q        = $qb->getQuery();
        $document = $q->getSingleResult();

        self::assertInstanceOf(Article::class, $document);
        self::assertEquals('Test Title', $document->getTitle());
        self::assertNull($document->getBody());

        $document->setTitle('changed');
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(Article::class)->findOne();
        self::assertEquals('changed', $check['title']);
        self::assertEquals('Test Body', $check['body']);
    }
}
