<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

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

      $user = $this->dm->findOne('Documents\User', array('username' => 'datetest2'));
      $this->assertTrue($user->getCreatedAt() instanceof \DateTime);
      $this->assertEquals('1900-01-01', $user->getCreatedAt()->format('Y-m-d'));
    }
}