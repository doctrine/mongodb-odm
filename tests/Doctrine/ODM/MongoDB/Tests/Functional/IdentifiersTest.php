<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

class IdentifiersTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIdentifiersAreSet()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $user->setPassword('test');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->getId() !== '');
    }

    public function testIdentityMap()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $query = $this->dm->createQuery('Documents\User')
            ->where('id', $user->getId());

        $user = $query->getSingleResult();
        $this->assertSame($user, $user);

        $this->dm->clear();

        $user2 = $query->getSingleResult();
        $this->assertNotSame($user, $user2);
    }

}