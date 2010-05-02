<?php

require_once 'TestInit.php';

class InheritanceTest extends BaseTest
{
    public function testIdentifiersAreSet()
    {
        $user = new \Documents\SpecialUser();
        $user->username = 'specialuser';
        $user->profile = new \Documents\Profile();
        $user->profile->firstName = 'Jon';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue(isset($user->id));
        $this->assertTrue(isset($user->profile->profileId));

        $query = $this->dm->createQuery('Documents\SpecialUser')
            ->where('id', $user->id)
            ->loadReference('profile')
            ->refresh();

        $user = $query->getSingleResult();
        
        $user->profile->lastName = 'Wage';
        $this->dm->flush();
        $this->dm->clear();
        
        $user = $query->getSingleResult();
        $this->assertEquals('Wage', $user->profile->lastName);
        $this->assertTrue($user instanceof Documents\SpecialUser);
    }
}