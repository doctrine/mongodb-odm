<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;

class PrimeTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testPrime()
    {
        $group1 = new \Documents\Group();
        $group2 = new \Documents\Group();
        $user1 = new \Documents\User();
        $user1->setGroups(new ArrayCollection(array($group1, $group2)));
        $account1 = new \Documents\Account();
        $user1->setAccount($account1);

        $user2 = new \Documents\User();
        $user2->setGroups(new ArrayCollection(array($group1, $group2)));
        $account2 = new \Documents\Account();
        $user2->setAccount($account2);

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($account1);
        $this->dm->persist($user1);
        $this->dm->persist($account2);
        $this->dm->persist($user2);

        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups')->prime(true)
            ->field('account')->prime(true);

        $query = $qb->getQuery();
        $users = $query->execute();
        foreach ($users as $user) {
            $this->assertTrue($user->getAccount()->__isInitialized__);
            foreach ($user->getGroups() as $group) {
                $this->assertNotEquals('Proxies\DocumentsGroupProxy', get_class($group));
            }
        }

        $this->dm->clear();

        $test = false;
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups')->prime(function() use (&$test) {
                $test = true;
            });

        $query = $qb->getQuery();
        $users = $query->execute();
        $this->assertTrue($test);
    }

    public function testMappedPrime()
    {
        $blogPost = new \Documents\BlogPost();
        $comment1 = new \Documents\Comment('1', new \DateTime());
        $comment2 = new \Documents\Comment('2', new \DateTime());

        $blogPost->addComment($comment1);
        $blogPost->addComment($comment2);

        $this->dm->persist($comment1);
        $this->dm->persist($comment2);
        $this->dm->persist($blogPost);

        $this->dm->flush();
        $this->dm->clear();

        $blogPosts = $this->dm->createQueryBuilder('Documents\BlogPost')
            ->field('comments')->prime(true)
            ->getQuery()->execute();

        foreach ($blogPosts as $blogPost) {
            $this->assertTrue($blogPost->comments->isInitialized());

            $comments = array_map(function($el) {
                return $el->text;
            }, iterator_to_array($blogPost->comments));
            $this->assertEquals(array('1', '2'), $comments);
        }
    }
}