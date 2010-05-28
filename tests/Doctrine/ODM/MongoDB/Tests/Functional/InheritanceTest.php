<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

class InheritanceTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIdentifiersAreSet()
    {
        $profile = new \Documents\Profile();
        $profile->setFirstName('Jon');

        $user = new \Documents\SpecialUser();
        $user->setUsername('specialuser');
        $user->setProfile($profile);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($user->getId() !== '');
        $this->assertTrue($user->getProfile()->getProfileId() !== '');

        $query = $this->dm->createQuery('Documents\SpecialUser')
            ->where('id', $user->getId());

        $user = $query->getSingleResult();
        
        $user->getProfile()->setLastName('Wage');
        $this->dm->flush();
        $this->dm->clear();
        
        $user = $query->getSingleResult();
        $this->assertEquals('Wage', $user->getProfile()->getLastName());
        $this->assertTrue($user instanceof \Documents\SpecialUser);
    }
}