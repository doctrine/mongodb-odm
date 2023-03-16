<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;

use function get_class;
use function time;

use const PHP_INT_SIZE;

class DateTest extends BaseTestCase
{
    public function testDates(): void
    {
        $user = new User();
        $user->setUsername('w00ting');
        $this->dm->persist($user);
        $this->dm->flush();

        self::assertInstanceOf(DateTime::class, $user->getCreatedAt());

        $user->setCreatedAt('1985-09-01 00:00:00');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'w00ting']);
        self::assertNotNull($user);
        self::assertEquals('w00ting', $user->getUsername());
        self::assertInstanceOf(DateTime::class, $user->getCreatedAt());
        self::assertEquals('09/01/1985', $user->getCreatedAt()->format('m/d/Y'));
    }

    /**
     * @param DateTime|UTCDateTime $oldValue
     * @param DateTime|UTCDateTime $newValue
     *
     * @dataProvider provideEquivalentDates
     */
    public function testDateInstanceChangeDoesNotCauseUpdateIfValueIsTheSame($oldValue, $newValue): void
    {
        $user = new User();
        $user->setCreatedAt($oldValue);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->findOneBy([]);
        $user->setCreatedAt($newValue);
        $this->dm->getUnitOfWork()->computeChangeSets();
        $changeset = $this->dm->getUnitOfWork()->getDocumentChangeSet($user);
        self::assertEmpty($changeset);
    }

    public static function provideEquivalentDates(): array
    {
        return [
            [new DateTime('1985-09-01 00:00:00'), new DateTime('1985-09-01 00:00:00')],
            [new DateTime('2012-07-11T14:55:14-04:00'), new DateTime('2012-07-11T19:55:14+01:00')],
            [new DateTime('@1342033881'), new UTCDateTime(1342033881000)],
            [DateTime::createFromFormat('U.u', '100000000.123'), new UTCDateTime(100000000123)],
            [DateTime::createFromFormat('U.u', '100000000.123000'), new UTCDateTime(100000000123)],
            [new UTCDateTime(100000000123), DateTime::createFromFormat('U.u', '100000000.123')],
        ];
    }

    public function testDateInstanceValueChangeDoesCauseUpdateIfValueIsTheSame(): void
    {
        $user = new User();
        $user->setCreatedAt(new DateTime('1985-09-01'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->findOneBy([]);
        self::assertInstanceOf(DateTime::class, $user->getCreatedAt());
        $user->getCreatedAt()->setTimestamp(time() - 3600);

        $this->dm->getUnitOfWork()->computeChangeSets();
        $changeset = $this->dm->getUnitOfWork()->getDocumentChangeSet($user);
        self::assertNotEmpty($changeset);
    }

    public function testOldDate(): void
    {
        if (PHP_INT_SIZE === 4) {
            $this->expectException(InvalidArgumentException::class);
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
        self::assertArrayHasKey('createdAt', $test);

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'datetest2']);
        self::assertInstanceOf(DateTime::class, $user->getCreatedAt());
        self::assertEquals('1900-01-01', $user->getCreatedAt()->format('Y-m-d'));
    }
}
