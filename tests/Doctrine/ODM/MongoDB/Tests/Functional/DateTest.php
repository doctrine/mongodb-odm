<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Account;
use Documents\Address;
use Documents\Phonenumber;
use Documents\User;

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

    /**
     * @dataProvider provideEquivalentDates
     */
    public function testDateInstanceChangeDoesNotCauseUpdateIfValueIsTheSame($oldValue, $newValue)
    {
        $user = new User();
        $user->setCreatedAt($oldValue);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->findOneBy(array());
        $user->setCreatedAt($newValue);
        $this->dm->getUnitOfWork()->computeChangeSets();
        $changeset = $this->dm->getUnitOfWork()->getDocumentChangeset($user);
        $this->assertEmpty($changeset);
    }

    public function provideEquivalentDates()
    {
        return array(
            array(new \DateTime('1985-09-01 00:00:00'), new \DateTime('1985-09-01 00:00:00')),
            array(new \DateTime('2012-07-11T14:55:14-04:00'), new \DateTime('2012-07-11T19:55:14+01:00')),
            array(new \DateTime('@1342033881'), new \MongoDate(1342033881)),
            array(\DateTime::createFromFormat('U.u', '100000000.123'), new \MongoDate(100000000, 123000)),
            array(\DateTime::createFromFormat('U.u', '100000000.123000'), new \MongoDate(100000000, 123000)),
            array(new \MongoDate(100000000, 123000), \DateTime::createFromFormat('U.u', '100000000.123')),
        );
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
        if (PHP_INT_SIZE === 4) {
            $this->setExpectedException("InvalidArgumentException");
        }

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
