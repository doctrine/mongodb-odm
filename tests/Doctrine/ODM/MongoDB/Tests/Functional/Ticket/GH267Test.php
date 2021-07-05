<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH267Test extends BaseTest
{
    public function testNestedReferences(): void
    {
        // Users
        $user1 = new GH267User('Tom Petty');
        $user2 = new GH267User('Grateful Dead');
        $user3 = new GH267User('Neil Young');

        // Company
        $company = new GH267BuyerCompany();

        $user1->setCompany($company);
        $user2->setCompany($company);
        $user3->setCompany($company);

        $this->dm->persist($company);
        $this->dm->flush();

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();

        // Get ids for use later
        $user1Id   = $user1->getId();
        $companyId = $company->getId();

        // Clear out DM and read from DB afresh
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(GH267User::class)
            ->field('_id')->equals($user1Id);

        $query  = $qb->getQuery();
        $result = $query->execute();
        $dbUser = $result->current();

        // Assert user name
        $this->assertEquals('Tom Petty', $dbUser->getName());

        // Assert company id
        $this->assertEquals($companyId, $dbUser->getCompany()->getId());

        // Assert number of users
        $this->assertEquals(3, $dbUser->getCompany()->getUsers()->count(true));
    }
}

/**
 * @ODM\Document(collection="users")
 */
class GH267User
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $name;

    /** @ODM\ReferenceOne(name="company", targetDocument=GH267Company::class, inversedBy="users") */
    protected $company;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setCompany($company): void
    {
        $this->company = $company;
    }

    public function getCompany()
    {
        return $this->company;
    }
}

/**
 * @ODM\Document(collection="companies")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"seller"=GH267SellerCompany::class, "buyer"=GH267BuyerCompany::class})
 */
class GH267Company
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceMany(targetDocument=GH267User::class, mappedBy="company") */
    protected $users;

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsers($users): void
    {
        $this->users = $users;
    }

    public function getUsers()
    {
        return $this->users;
    }
}

/**
 * @ODM\Document(collection="companies")
 */
class GH267BuyerCompany extends GH267Company
{
}

/**
 * @ODM\Document(collection="companies")
 */
class GH267SellerCompany extends GH267Company
{
}
