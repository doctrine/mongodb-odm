<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Address,
    Documents\Profile,
    Documents\Phonenumber,
    Documents\Account,
    Documents\Group,
    Documents\User,
    Doctrine\ODM\MongoDB\PersistentCollection;

class ReferencesTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testManyDeleteReference()
    {
        $user = new \Documents\User();
        
        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));
        
        $this->dm->persist($user);
        $this->dm->flush();
        
        $this->dm->clear();
        
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();

        $this->dm->remove($user2);
        $this->dm->flush();
        
        $qb = $this->dm->createQueryBuilder('Documents\Group');
        $query = $qb->getQuery();
        $groups = $query->execute();
        
        $count = $groups->count();
        
        $this->assertEquals(0, $count);
    }

    public function testLazyLoadReference()
    {
        $user = new User();
        $profile = new Profile();
        $profile->setFirstName('Jonathan');
        $profile->setLastName('Wage');
        $user->setProfile($profile);
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();

        $user = $query->getSingleResult();

        $profile = $user->getProfile();

        $this->assertTrue($profile instanceof \Proxies\__CG__\Documents\Profile);

        $profile->getFirstName();

        $this->assertEquals('Jonathan', $profile->getFirstName());
        $this->assertEquals('Wage', $profile->getLastName());
    }

    public function testOneEmbedded()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $address->setCity('Nashville');
        $address->setState('TN');
        $address->setZipcode('37209');

        $user = new User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $user->setAddress($address);

        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $this->assertEquals($user->getAddress(), $user2->getAddress());
    }

    public function testManyEmbedded()
    {
        $user = new \Documents\User();
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6153303769'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $this->assertEquals($user->getPhonenumbers()->toArray(), $user2->getPhonenumbers()->toArray());
    }

    public function testOneReference()
    {
        $account = new Account();
        $account->setName('Test Account');

        $user = new User();
        $user->setUsername('jwage');
        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->flush();
        $this->dm->clear();

        $accountId = $user->getAccount()->getId();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
    }

    public function testManyReference()
    {
        $user = new \Documents\User();
        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $groups = $user->getGroups();

        $this->assertTrue($groups instanceof PersistentCollection);
        $this->assertTrue($groups[0]->getId() !== '');
        $this->assertTrue($groups[1]->getId() !== '');
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $groups = $user2->getGroups();
        $this->assertFalse($groups->isInitialized());

        $groups->count();
        $this->assertFalse($groups->isInitialized());

        $groups->isEmpty();
        $this->assertFalse($groups->isInitialized());

        $groups = $user2->getGroups();

        $this->assertTrue($groups instanceof PersistentCollection);
        $this->assertTrue($groups[0] instanceof Group);
        $this->assertTrue($groups[1] instanceof Group);

        $this->assertTrue($groups->isInitialized());

        unset($groups[0]);
        $groups[1]->setName('test');

        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user3 = $query->getSingleResult();
        $groups = $user3->getGroups();

        $this->assertEquals('test', $groups[0]->getName());
        $this->assertEquals(1, count($groups));
    }
}