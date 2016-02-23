<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH267Test extends BaseTest
{
    public function testNestedReferences()
    {
        // Users
        $user1 = new User('Tom Petty');
        $user2 = new User('Grateful Dead');
        $user3 = new User('Neil Young');

        // Company
        $company = new BuyerCompany();

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
        $user1Id = $user1->getId();
        $companyId = $company->getId();

        // Clear out DM and read from DB afresh
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__ . '\User')
            ->field('_id')->equals($user1Id);

        $query = $qb->getQuery();
        $dbUser = $query->execute()->getNext();

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
class User
{   
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $name;

    /**
     * @ODM\ReferenceOne(name="company", targetDocument="Company", discriminatorMap={"seller"="SellerCompany", "buyer"="BuyerCompany"}, inversedBy="users")
     */
    protected $company;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setId($id) 
    {
        $this->id = $id;
    }

    public function getId() 
    {
        return $this->id;
    }

    public function setName($name) 
    {
        $this->name = $name;
    }

    public function getName() 
    {
        return $this->name;
    }

    public function setCompany($company) 
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
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"seller"="SellerCompany", "buyer"="BuyerCompany"}) 
 */
class Company
{   
    /** @ODM\Id */
    protected $id;

    /**
     * @ODM\ReferenceMany(targetDocument="User", mappedBy="company")
     */
    protected $users;

    public function setId($id) 
    {
        $this->id = $id;
    }

    public function getId() 
    {
        return $this->id;
    }

    public function setUsers($users) 
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
class BuyerCompany extends Company
{   

}

/**
 * @ODM\Document(collection="companies")
 */
class SellerCompany extends Company
{   

}
