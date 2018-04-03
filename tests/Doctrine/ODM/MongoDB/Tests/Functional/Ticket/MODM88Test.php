<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Article;

class MODM88Test extends BaseTest
{
    public function testTest()
    {
        $article = new Article();
        $article->setTitle('Test Title');
        $article->setBody('Test Body');
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(Article::class)
            ->select('_id', 'title');
        $q = $qb->getQuery();
        $document = $q->getSingleResult();

        $this->assertEquals('Test Title', $document->getTitle());
        $this->assertNull($document->getBody());

        $document->setTitle('changed');
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(Article::class)->findOne();
        $this->assertEquals('changed', $check['title']);
        $this->assertEquals('Test Body', $check['body']);
    }
}
