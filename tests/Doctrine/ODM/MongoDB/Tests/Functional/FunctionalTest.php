<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\User;

class FunctionalTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIncrement()
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(100);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));

        $user->incrementCount(5);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));
        $this->assertEquals(105, $user->getCount());
    }
}