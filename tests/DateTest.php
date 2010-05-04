<?php

require_once 'TestInit.php';

use Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

class DateTest extends BaseTest
{
    public function testDates()
    {
        $user = new User();
        $user->setUsername('w00ting');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->getCreatedAt() instanceof \DateTime);

        $user->setCreatedAt('1985-09-01 00:00:00');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneByUsername('w00ting');
        $this->assertEquals('w00ting', $user->getUsername());
        $this->assertTrue($user->getCreatedAt() instanceof \DateTime);
        $this->assertEquals('09/01/1985', $user->getCreatedAt()->format('m/d/Y'));
    }
}