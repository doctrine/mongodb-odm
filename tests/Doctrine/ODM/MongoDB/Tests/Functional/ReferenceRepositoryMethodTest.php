<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime,
    Doctrine\ODM\MongoDB\PersistentCollection;

class ReferenceRepositoryMethodTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testOneToOne()
    {
        $date1 = new DateTime();
        $date1->setTimestamp(strtotime('-20 seconds'));

        $date2 = new DateTime();
        $date2->setTimestamp(strtotime('-10 seconds'));

        $blogPost = new \Documents\BlogPost('Test');
        $blogPost->addComment(new \Documents\Comment('Comment 1', $date1));
        $blogPost->addComment(new \Documents\Comment('Comment 2', $date2));
        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $blogPost = $this->dm->createQueryBuilder('Documents\BlogPost')
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals('Comment 2', $blogPost->repoComment->getText());
        $this->assertEquals('Comment 1', $blogPost->repoComments[0]->getText());
        $this->assertEquals('Comment 2', $blogPost->repoComments[1]->getText());
    }
}