<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

class DateTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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
        $this->assertNotNull($user);
        $this->assertEquals('w00ting', $user->getUsername());
        $this->assertTrue($user->getCreatedAt() instanceof \DateTime);
        $this->assertEquals('09/01/1985', $user->getCreatedAt()->format('m/d/Y'));
    }

    public function testDateInstanceChangeDoesNotCauseUpdateIfValueIsTheSame()
    {
        $user = new User();
        $user->setCreatedAt(new \DateTime('1985-09-01 00:00:00'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->findOneBy(array());
        $user->setCreatedAt(new \DateTime('1985-09-01 00:00:00'));
        $this->dm->getUnitOfWork()->computeChangeSets();
        $changeset = $this->dm->getUnitOfWork()->getDocumentChangeset($user);
        $this->assertEmpty($changeset);
    }

    public function testDateInstanceValueChangeDoesCauseUpdateIfValueIsTheSame()
    {
        $user = new User();
        $user->setCreatedAt(new \DateTime('1985-09-01'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->findOneBy(array());
        $user->getCreatedAt()->setTimestamp(time() - 3600);

        $this->dm->getUnitOfWork()->computeChangeSets();
        $changeset = $this->dm->getUnitOfWork()->getDocumentChangeset($user);
        $this->assertNotEmpty($changeset);
    }

    public function testOldDate()
    {
        $user = new User();
        $user->setUsername('datetest');
        $user->setCreatedAt('1900-01-01');
        $this->dm->persist($user);
        $this->dm->flush();

        $user->setUsername('datetest2');
        $this->dm->flush();

        $this->dm->clear();

        $test = $this->dm->getDocumentCollection('Documents\User')->findOne(array('username' => 'datetest2'));
        $this->assertTrue(isset($test['createdAt']));

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'datetest2'));
        $this->assertTrue($user->getCreatedAt() instanceof \DateTime);
        $this->assertEquals('1900-01-01', $user->getCreatedAt()->format('Y-m-d'));
    }
}