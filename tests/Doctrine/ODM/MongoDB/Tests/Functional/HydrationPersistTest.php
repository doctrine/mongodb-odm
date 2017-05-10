<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Address;
use Documents\Phonenumber;
use Documents\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class HydrationPersistTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function test()
    {
        // Persist a user with an embedded document
        $user = new User();
        $user->setUsername('anushr');
        $address = new Address();
        $address->setCity('Bangalore');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();

        $id = $user->getId();

        // Hydrate with some new data
        $data = array(
            'username' => 'jwage',
            'address'  => array(
            	'city' => 'Nashville'
        ));
        $this->dm->getHydratorFactory()->hydrate($user, $data);
        $this->dm->flush();

        // Detach from DM so that the DM will fetch directly from DB
        $this->dm->detach($user);
        unset($user);

        // Assert that hydrated values have been persisted
        $user = $this->dm->find('Documents\User', $id);
        $this->assertEquals($user->getUsername(), 'jwage');
        $this->assertEquals($user->getAddress()->getCity(), 'Nashville');
    }
}