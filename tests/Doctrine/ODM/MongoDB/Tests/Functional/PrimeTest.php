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
}