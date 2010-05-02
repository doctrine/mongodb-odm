<?php

require_once 'TestInit.php';

class IdentifiersTest extends BaseTest
{
    public function testIdentifiersAreSet()
    {
        $user = $this->_createTestUser();
        
        $this->assertTrue(isset($user->id));
        $this->assertTrue(isset($user->profile->profileId));
    }

    public function testIdentityMap()
    {
        $user = $this->_createTestUser();
        $query = $this->dm->createQuery('Documents\User')
            ->where('id', $user->id);

        $user = $query->getSingleResult();
        $this->assertSame($user, $user);

        $this->dm->clear();

        $user2 = $query->getSingleResult();
        $this->assertNotSame($user, $user2);
    }

}