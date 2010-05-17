<?php

require_once 'TestInit.php';

use Documents\Address,
    Documents\Profile,
    Documents\Phonenumber,
    Documents\Account,
    Documents\Group,
    Documents\User,
    Doctrine\ODM\MongoDB\PersistentCollection;

class ReferencesTest extends BaseTest
{
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

        $query = $this->dm->createQuery('Documents\User')
            ->where('id', $user->getId());

        $user = $query->getSingleResult();

        $profile = $user->getProfile();

        $this->assertTrue($profile instanceof \Proxies\DocumentsProfileProxy);

        $profile->getFirstName();

        $this->assertEquals('Jonathan', $profile->getFirstName());
        $this->assertEquals('Wage', $profile->getLastName());

    }

    public function testOneEmbeddedReference()
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

        $user2 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->getId())
            ->getSingleResult();
        $this->assertEquals($user->getAddress(), $user2->getAddress());
    }

    public function testManyEmbeddedReference()
    {
        $user = new \Documents\User();
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6153303769'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->getId())
            ->getSingleResult();

        $this->assertEquals($user->getPhonenumbers(), $user2->getPhonenumbers());
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

        $user2 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->getId())
            ->getSingleResult();

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

        $user2 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->getId())
            ->getSingleResult();

        $groups = $user2->getGroups();

        $this->assertTrue($groups instanceof PersistentCollection);
        $this->assertTrue($groups[0] instanceof Group);
        $this->assertTrue($groups[1] instanceof Group);

        unset($groups[0]);

        $this->dm->flush();
        $this->dm->clear();

        $user3 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->getId())
            ->getSingleResult();
        $this->assertEquals(1, count($user2->getGroups()));
    }
}