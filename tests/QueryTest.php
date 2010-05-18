<?php

require_once 'TestInit.php';

use Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

class QueryTest extends BaseTest
{
    public function testBasicQuery()
    {
        $user = new User();
        $user->setUsername('boo');
        $this->dm->persist($user);
        $this->dm->flush();

        $query = $this->dm->createQuery('Documents\User')
            ->where('$where', "function() { return this.username == 'boo' }");
        $user = $query->getSingleResult();
        $this->assertEquals('boo', $user->getUsername());

        $query = $this->dm->createQuery('Documents\User')
            ->reduce("function() { return this.username == 'boo' }");
        $user = $query->getSingleResult();
        $this->assertEquals('boo', $user->getUsername());

    }
}