<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

class MODM88Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $article = new \Documents\Article;
        $article->setTitle('Test Title');
        $article->setBody('Test Body');
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\Article')
            ->select('_id', 'title');
        $q = $qb->getQuery();
        $document = $q->getSingleResult();
        
        $this->assertEquals('Test Title', $document->getTitle());
        $this->assertNull($document->getBody());

        $document->setTitle('changed');
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection('Documents\Article')->findOne();
        $this->assertEquals('changed', $check['title']);
        $this->assertEquals('Test Body', $check['body']);
    }
}