<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\LockMode;
use Documents\Account;
use Documents\Address;
use Documents\Group;
use Documents\Phonenumber;
use Documents\User;

class DocumentRepositoryTest extends BaseTest
{
    public function testMatchingAcceptsCriteriaWithNullWhereExpression()
    {
        $repository = $this->dm->getRepository('Documents\User');
        $criteria = new Criteria();

        $this->assertNull($criteria->getWhereExpression());
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $repository->matching($criteria));
    }

    public function testFindWithOptimisticLockAndNoDocumentFound()
    {
        $invalidId = 'test';

        $repository = $this->dm->getRepository('Documents\VersionedDocument');

        $document = $repository->find($invalidId, LockMode::OPTIMISTIC);
        $this->assertNull($document);
    }

    public function testFindByRefOneFull()
    {
        $user = new User();
        $account = new Account('name');
        $user->setAccount($account);
        $this->dm->persist($user);
        $this->dm->persist($account);
        $this->dm->flush();
        $this->assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['account' => $account]));
    }

    public function testFindByRefOneSimple()
    {
        $user = new User();
        $account = new Account('name');
        $user->setAccountSimple($account);
        $this->dm->persist($user);
        $this->dm->persist($account);
        $this->dm->flush();
        $this->assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['accountSimple' => $account]));
    }

    public function testFindByEmbedOne()
    {
        $user = new User();
        $address = new Address();
        $address->setCity('Cracow');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['address' => $address]));
    }

    public function testFindByRefManyFull()
    {
        $user = new User();
        $group = new Group('group');
        $user->addGroup($group);
        $this->dm->persist($user);
        $this->dm->persist($group);
        $this->dm->flush();
        $this->assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['groups' => $group]));
    }

    public function testFindByRefManySimple()
    {
        $user = new User();
        $group = new Group('group');
        $user->addGroupSimple($group);
        $this->dm->persist($user);
        $this->dm->persist($group);
        $this->dm->flush();
        $this->assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['groupsSimple' => $group]));
    }

    public function testFindByEmbedMany()
    {
        $user = new User();
        $phonenumber = new Phonenumber('12345678');
        $user->addPhonenumber($phonenumber);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['phonenumbers' => $phonenumber]));
    }
}
