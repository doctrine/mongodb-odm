<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use MongoDB\BSON\UTCDateTime;
use const PHP_INT_SIZE;
use function get_class;
use function time;

class DateTest extends BaseTest
{
    public function testDates()
    {
        $user = new User();
        $user->setUsername('w00ting');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertInstanceOf(\DateTime::class, $user->getCreatedAt());

        $user->setCreatedAt('1985-09-01 00:00:00');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'w00ting']);
        $this->assertNotNull($user);
        $this->assertEquals('w00ting', $user->getUsername());
        $this->assertInstanceOf(\DateTime::class, $user->getCreatedAt());
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

        $user = $this->dm->getRepository(get_class($user))->findOneBy([]);
        $user->setCreatedAt($newValue);
        $this->dm->getUnitOfWork()->computeChangeSets();
        $changeset = $this->dm->getUnitOfWork()->getDocumentChangeset($user);
        $this->assertEmpty($changeset);
    }

    public function provideEquivalentDates()
    {
        return [
            [new \DateTime('1985-09-01 00:00:00'), new \DateTime('1985-09-01 00:00:00')],
            [new \DateTime('2012-07-11T14:55:14-04:00'), new \DateTime('2012-07-11T19:55:14+01:00')],
            [new \DateTime('@1342033881'), new UTCDateTime(1342033881000)],
            [\DateTime::createFromFormat('U.u', '100000000.123'), new UTCDateTime(100000000123)],
            [\DateTime::createFromFormat('U.u', '100000000.123000'), new UTCDateTime(100000000123)],
            [new UTCDateTime(100000000123), \DateTime::createFromFormat('U.u', '100000000.123')],
        ];
    }

    public function testDateInstanceValueChangeDoesCauseUpdateIfValueIsTheSame()
    {
        $user = new User();
        $user->setCreatedAt(new \DateTime('1985-09-01'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->findOneBy([]);
        $user->getCreatedAt()->setTimestamp(time() - 3600);

        $this->dm->getUnitOfWork()->computeChangeSets();
        $changeset = $this->dm->getUnitOfWork()->getDocumentChangeset($user);
        $this->assertNotEmpty($changeset);
    }

    public function testOldDate()
    {
        if (PHP_INT_SIZE === 4) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $user = new User();
        $user->setUsername('datetest');
        $user->setCreatedAt('1900-01-01');
        $this->dm->persist($user);
        $this->dm->flush();

        $user->setUsername('datetest2');
        $this->dm->flush();

        $this->dm->clear();

        $test = $this->dm->getDocumentCollection(User::class)->findOne(['username' => 'datetest2']);
        $this->assertArrayHasKey('createdAt', $test);

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'datetest2']);
        $this->assertInstanceOf(\DateTime::class, $user->getCreatedAt());
        $this->assertEquals('1900-01-01', $user->getCreatedAt()->format('Y-m-d'));
    }
}
