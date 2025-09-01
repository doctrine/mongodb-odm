<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\Versionable;
use MongoDB\BSON\ObjectId;

class VersionableTest extends BaseTestCase
{
    public function testIntNextVersion(): void
    {
        $type = $this->getType(Type::INT);
        self::assertSame(1, $type->getNextVersion(null));
        self::assertSame(2, $type->getNextVersion(1));
    }

    public function testDecimal128NextVersion(): void
    {
        $type = $this->getType(Type::DECIMAL128);
        self::assertSame('1', $type->getNextVersion(null));
        self::assertSame('2', $type->getNextVersion('1'));
    }

    public function testDateTimeNextVersion(): void
    {
        $type    = $this->getType(Type::DATE);
        $current = new DateTime();
        $next    = $type->getNextVersion(null);
        self::assertInstanceOf(DateTime::class, $next);
        self::assertGreaterThanOrEqual($current, $next);
        self::assertLessThanOrEqual(new DateTime(), $next);

        $next = $type->getNextVersion(new DateTime('2000-01-01'));
        self::assertInstanceOf(DateTime::class, $next);
        self::assertGreaterThanOrEqual($current, $next);
        self::assertLessThanOrEqual(new DateTime(), $next);
    }

    public function testDateTimeImmutableNextVersion(): void
    {
        $type    = $this->getType(Type::DATE_IMMUTABLE);
        $current = new DateTime();
        $next    = $type->getNextVersion(null);
        self::assertInstanceOf(DateTimeImmutable::class, $next);
        self::assertGreaterThanOrEqual($current, $next);
        self::assertLessThanOrEqual(new DateTimeImmutable(), $next);

        $next = $type->getNextVersion(new DateTimeImmutable('2000-01-01'));
        self::assertInstanceOf(DateTimeImmutable::class, $next);
        self::assertGreaterThanOrEqual($current, $next);
        self::assertLessThanOrEqual(new DateTimeImmutable(), $next);
    }

    public function testObjectIdNextVersion(): void
    {
        $type    = $this->getType(Type::OBJECTID);
        $current = new ObjectId();
        $next    = $type->getNextVersion(null);
        self::assertInstanceOf(ObjectId::class, $next);
        self::assertGreaterThan($current, $next);
        self::assertLessThan(new ObjectId(), $next);

        $next = $type->getNextVersion($current);
        self::assertInstanceOf(ObjectId::class, $next);
        self::assertGreaterThan($current, $next);
        self::assertLessThan(new ObjectId(), $next);
    }

    private function getType(string $name): Versionable
    {
        $type = Type::getType($name);

        self::assertInstanceOf(Versionable::class, $type);

        return $type;
    }
}
