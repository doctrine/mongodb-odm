<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\Address,
    Documents\Profile,
    Documents\Phonenumber,
    Documents\Account,
    Documents\Group,
    Documents\User,
    Documents\Functional\EmbeddedTestLevel0,
    Documents\Functional\EmbeddedTestLevel1,
    Doctrine\ODM\MongoDB\PersistentCollection;

class EmbeddedTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFlushEmbedded()
    {
        $test = new EmbeddedTestLevel0();
        $test->name = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->findOne('Documents\Functional\EmbeddedTestLevel0', array('name' => 'test'));
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel0', $test);

        // Adding this flush here makes level1 not to be inserted.
        $this->dm->flush();

        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->findOne('Documents\Functional\EmbeddedTestLevel0');
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel0', $test);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel1', $test->level1[0]);
    }

    public function testOneEmbedded()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $address->setCity('Nashville');
        $address->setState('TN');
        $address->setZipcode('37209');

        $addressClone = clone $address;

        $user = new User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $user->setAddress($address);

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->createQuery('Documents\User')
            ->field('id')->equals($user->getId())
            ->getSingleResult();
        $this->assertEquals($addressClone, $user->getAddress());
    }

    public function testManyEmbedded()
    {
        $user = new \Documents\User();
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6153303769'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQuery('Documents\User')
            ->id()->equals($user->getId())
            ->getSingleResult();

        $this->assertEquals($user->getPhonenumbers(), $user2->getPhonenumbers()->unwrap());
    }
}