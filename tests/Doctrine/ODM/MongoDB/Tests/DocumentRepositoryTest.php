<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Documents\Account;
use Documents\Address;
use Documents\Developer;
use Documents\Group;
use Documents\Phonenumber;
use Documents\SubProject;
use Documents\User;
use MongoDB\BSON\ObjectId;

use const DOCTRINE_MONGODB_DATABASE;

class DocumentRepositoryTest extends BaseTestCase
{
    public function testMatchingAcceptsCriteriaWithNullWhereExpression(): void
    {
        $repository = $this->dm->getRepository(User::class);
        $criteria   = new Criteria();

        self::assertNull($criteria->getWhereExpression());
        self::assertInstanceOf(Collection::class, $repository->matching($criteria));
    }

    public function testFindByRefOneFull(): void
    {
        $user    = new User();
        $account = new Account();
        $user->setAccount($account);
        $this->dm->persist($user);
        $this->dm->persist($account);
        $this->dm->flush();

        $query         = $this->dm
            ->getUnitOfWork()
            ->getDocumentPersister(User::class)
            ->prepareQueryOrNewObj(['account' => $account]);
        $expectedQuery = ['account.$id' => new ObjectId($account->getId())];
        self::assertEquals($expectedQuery, $query);

        self::assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['account' => $account]));
    }

    public function testFindByRefOneWithoutTargetDocumentFull(): void
    {
        $user    = new User();
        $account = new Account();
        $user->setAccount($account);
        $this->dm->persist($user);
        $this->dm->persist($account);
        $this->dm->flush();

        $query         = $this->dm
            ->getUnitOfWork()
            ->getDocumentPersister(Account::class)
            ->prepareQueryOrNewObj(['user' => $user]);
        $expectedQuery = [
            'user.$ref' => 'users',
            'user.$id' => new ObjectId($user->getId()),
            'user.$db' => DOCTRINE_MONGODB_DATABASE,
        ];
        self::assertEquals($expectedQuery, $query);

        self::assertSame($account, $this->dm->getRepository(Account::class)->findOneBy(['user' => $user]));
    }

    public function testFindByRefOneWithoutTargetDocumentStoredAsDbRef(): void
    {
        $user    = new User();
        $account = new Account();
        $account->setUserDbRef($user);
        $this->dm->persist($user);
        $this->dm->persist($account);
        $this->dm->flush();

        $query         = $this->dm
            ->getUnitOfWork()
            ->getDocumentPersister(Account::class)
            ->prepareQueryOrNewObj(['userDbRef' => $user]);
        $expectedQuery = [
            'userDbRef.$ref' => 'users',
            'userDbRef.$id' => new ObjectId($user->getId()),
        ];
        self::assertEquals($expectedQuery, $query);

        self::assertSame($account, $this->dm->getRepository(Account::class)->findOneBy(['userDbRef' => $user]));
    }

    public function testFindDiscriminatedByRefManyFull(): void
    {
        $project   = new SubProject('mongodb-odm');
        $developer = new Developer('alcaeus', new ArrayCollection([$project]));
        $this->dm->persist($project);
        $this->dm->persist($developer);
        $this->dm->flush();

        $query         = $this->dm
            ->getUnitOfWork()
            ->getDocumentPersister(Developer::class)
            ->prepareQueryOrNewObj(['projects' => $project]);
        $expectedQuery = ['projects' => ['$elemMatch' => ['$id' => new ObjectId($project->getId())]]];
        self::assertEquals($expectedQuery, $query);

        self::assertSame($developer, $this->dm->getRepository(Developer::class)->findOneBy(['projects' => $project]));
    }

    public function testFindByRefOneSimple(): void
    {
        $user    = new User();
        $account = new Account();
        $user->setAccountSimple($account);
        $this->dm->persist($user);
        $this->dm->persist($account);
        $this->dm->flush();
        self::assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['accountSimple' => $account]));
    }

    public function testFindByEmbedOne(): void
    {
        $user    = new User();
        $address = new Address();
        $address->setCity('Cracow');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['address' => $address]));
    }

    public function testFindByRefManyFull(): void
    {
        $user  = new User();
        $group = new Group('group');
        $user->addGroup($group);
        $this->dm->persist($user);
        $this->dm->persist($group);
        $this->dm->flush();

        $query = $this->dm
            ->getUnitOfWork()
            ->getDocumentPersister(User::class)
            ->prepareQueryOrNewObj(['groups' => $group]);

        $expectedQuery = [
            'groups' => [
                '$elemMatch' => [
                    '$id' => new ObjectId($group->getId()),
                ],
            ],
        ];
        self::assertEquals($expectedQuery, $query);

        self::assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['groups' => $group]));
    }

    public function testFindByRefManySimple(): void
    {
        $user  = new User();
        $group = new Group('group');
        $user->addGroupSimple($group);
        $this->dm->persist($user);
        $this->dm->persist($group);
        $this->dm->flush();
        self::assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['groupsSimple' => $group]));
    }

    public function testFindByEmbedMany(): void
    {
        $user        = new User();
        $phonenumber = new Phonenumber('12345678');
        $user->addPhonenumber($phonenumber);
        $this->dm->persist($user);
        $this->dm->flush();
        self::assertSame($user, $this->dm->getRepository(User::class)->findOneBy(['phonenumbers' => $phonenumber]));
    }

    public function testFindOneByWithSort(): void
    {
        $george = new User();
        $george->setUsername('George');
        $andy = new User();
        $andy->setUsername('Andy');
        $this->dm->persist($george);
        $this->dm->persist($andy);
        $this->dm->flush();
        self::assertSame($andy, $this->dm->getRepository(User::class)->findOneBy([], ['username' => 'ASC']));
        self::assertSame($george, $this->dm->getRepository(User::class)->findOneBy([], ['username' => 'DESC']));
    }
}
