<?php

require_once 'TestInit.php';

use Documents\Address,
    Documents\Profile,
    Documents\Phonenumber,
    Documents\Account,
    Documents\Group;

class ReferencesFunctionalTest extends BaseTest
{
    public function testLazyLoadReference()
    {
        $user = $this->_createTestUser();

        $query = $this->dm->createQuery('Documents\User')
            ->where('id', $user->id)
            ->refresh();
        $user = $query->getSingleResult();

        $this->assertEquals('profiles', $user->profile['$ref']);
        $this->assertTrue($user->profile['$id'] instanceof \MongoId);

        $this->dm->loadDocumentReference($user, 'profile');
        $this->assertEquals('Jonathan', $user->profile->firstName);
        $this->assertEquals('Wage', $user->profile->lastName);
    }

    public function testLoadReferenceInQuery()
    {
        $user = $this->_createTestUser();
        $query = $this->dm->createQuery('Documents\User')
            ->where('id', $user->id)
            ->loadReference('profile');
        $user4 = $query->getSingleResult();

        $this->assertEquals('Jonathan', $user4->profile->firstName);
        $this->assertEquals('Wage', $user4->profile->lastName);
    }

    public function testOneEmbeddedReference()
    {
        $user = $this->_createTestUser();
        $user->address = new Address();
        $user->address->address = '6512 Mercomatic Ct.';
        $user->address->city = 'Nashville';
        $user->address->state = 'TN';
        $user->address->zipcode = '37209';

        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->id)
            ->getSingleResult();
        $this->assertEquals($user->address, $user2->address);
    }

    public function testManyEmbeddedReference()
    {
        $user = $this->_createTestUser();
        $user->phonenumbers[] = new Phonenumber('6155139185');
        $user->phonenumbers[] = new Phonenumber('6153303769');
    
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->id)
            ->getSingleResult();

        $this->assertEquals($user->phonenumbers, $user2->phonenumbers);
    }

    public function testOneReference()
    {
        $user = $this->_createTestUser();

        $user->account = new Account();
        $user->account->name = 'Test Account';

        $this->dm->flush();
        $this->dm->clear();

        $accountId = $user->account->id;

        $user2 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->id)
            ->loadReference('account')
            ->getSingleResult();

        $this->assertEquals($user->account, $user2->account);
        $this->assertEquals($accountId, $user2->account->id);
    }

    public function testManyReference()
    {
        $user = $this->_createTestUser();

        $user->groups[] = new Group('Group 1');
        $user->groups[] = new Group('Group 2');

        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue(isset($user->groups[0]->id));
        $this->assertTrue(isset($user->groups[1]->id));

        $user2 = $this->dm->createQuery('Documents\User')
            ->where('id', $user->id)
            ->refresh()
            ->getSingleResult();

        $this->assertTrue($user2->groups[0]['$id'] instanceof \MongoId);
        $this->assertTrue($user2->groups[1]['$id'] instanceof \MongoId);

        $this->dm->loadDocumentReference($user2, 'groups');

        $this->assertTrue($user2->groups[0] instanceof Group);
        $this->assertTrue($user2->groups[1] instanceof Group);
    }
}