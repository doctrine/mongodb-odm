<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Types\DateImmutableType;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use stdClass;

use function assert;
use function date;
use function strtotime;

use const PHP_INT_SIZE;

class DateImmutableTypeTest extends TestCase
{
    public function testGetDateTime(): void
    {
        $type = Type::getType(Type::DATE_IMMUTABLE);
        assert($type instanceof DateImmutableType);

        $timestamp = 100000000.001;
        $dateTime  = $type->getDateTime($timestamp);
        self::assertEquals($timestamp, $dateTime->format('U.u'));

        $mongoDate = new UTCDateTime(100000000001);
        $dateTime  = $type->getDateTime($mongoDate);
        self::assertEquals($timestamp, $dateTime->format('U.u'));
    }

    public function testConvertToDatabaseValue(): void
    {
        $type = Type::getType(Type::DATE_IMMUTABLE);

        self::assertNull($type->convertToDatabaseValue(null), 'null is not converted');

        $mongoDate = new UTCDateTime();
        self::assertSame($mongoDate, $type->convertToDatabaseValue($mongoDate), 'MongoDate objects are not converted');

        $timestamp = 100000000.123;
        $dateTime  = DateTimeImmutable::createFromFormat('U.u', (string) $timestamp);
        $mongoDate = new UTCDateTime(100000000123);
        self::assertEquals($mongoDate, $type->convertToDatabaseValue($dateTime), 'DateTimeImmutable objects are converted to MongoDate objects');
        self::assertEquals($mongoDate, $type->convertToDatabaseValue($timestamp), 'Numeric timestamps are converted to MongoDate objects');
        self::assertEquals($mongoDate, $type->convertToDatabaseValue('' . $timestamp), 'String dates are converted to MongoDate objects');
        self::assertEquals($mongoDate, $type->convertToDatabaseValue($mongoDate), 'MongoDate objects are converted to MongoDate objects');
        self::assertEquals(null, $type->convertToDatabaseValue(null), 'null are converted to null');
    }

    public function testConvertDateTime(): void
    {
        $type = Type::getType(Type::DATE);

        $timestamp = 100000000.123;
        $mongoDate = new UTCDateTime(100000000123);

        $dateTime = DateTime::createFromFormat('U.u', (string) $timestamp);
        self::assertEquals($mongoDate, $type->convertToDatabaseValue($dateTime), 'DateTime objects are converted to MongoDate objects');
    }

    public function testConvertOldDate(): void
    {
        $type = Type::getType(Type::DATE_IMMUTABLE);

        $date      = new DateTimeImmutable('1900-01-01 00:00:00.123', new DateTimeZone('UTC'));
        $timestamp = '-2208988800.123';
        self::assertEquals($type->convertToDatabaseValue($timestamp), $type->convertToDatabaseValue($date));
    }

    /**
     * @param mixed $value
     *
     * @dataProvider provideInvalidDateValues
     */
    public function testConvertToDatabaseValueWithInvalidValues($value): void
    {
        $type = Type::getType(Type::DATE_IMMUTABLE);
        $this->expectException(InvalidArgumentException::class);
        $type->convertToDatabaseValue($value);
    }

    public static function provideInvalidDateValues(): array
    {
        return [
            'array'  => [[]],
            'string' => ['whatever'],
            'bool'   => [false],
            'object' => [new stdClass()],
            'invalid string' => ['foo'],
        ];
    }

    /**
     * @param mixed $input
     *
     * @dataProvider provideDatabaseToPHPValues
     */
    public function testConvertToPHPValue($input, DateTimeImmutable $output): void
    {
        $type   = Type::getType(Type::DATE_IMMUTABLE);
        $return = $type->convertToPHPValue($input);

        self::assertInstanceOf('DateTimeImmutable', $return);
        $this->assertTimestampEquals($output, $return);
    }

    public function testConvertToPHPValueDoesNotConvertNull(): void
    {
        $type = Type::getType(Type::DATE_IMMUTABLE);

        self::assertNull($type->convertToPHPValue(null));
    }

    /**
     * @param mixed $input
     *
     * @dataProvider provideDatabaseToPHPValues
     */
    public function testClosureToPHP($input, DateTimeImmutable $output): void
    {
        $type = Type::getType(Type::DATE_IMMUTABLE);

        $return = (static function ($value) use ($type) {
            $return = null;
            eval($type->closureToPHP());

            return $return;
        })($input);

        // @phpstan-ignore-next-line
        self::assertInstanceOf(DateTimeImmutable::class, $return);
        $this->assertTimestampEquals($output, $return);
    }

    public static function provideDatabaseToPHPValues(): array
    {
        $yesterday = strtotime('yesterday');
        $mongoDate = new UTCDateTime($yesterday * 1000);
        $dateTime  = new DateTimeImmutable('@' . $yesterday);

        return [
            [$dateTime, $dateTime],
            [$mongoDate, $dateTime],
            [$yesterday, $dateTime],
            [date('c', $yesterday), $dateTime],
            [new UTCDateTime(100000000123), DateTimeImmutable::createFromFormat('U.u', '100000000.123')],
        ];
    }

    public function test32bit1900Date(): void
    {
        if (PHP_INT_SIZE !== 4) {
            $this->markTestSkipped('Platform is not 32-bit');
        }

        $type = Type::getType(Type::DATE_IMMUTABLE);
        $this->expectException(InvalidArgumentException::class);
        $type->convertToDatabaseValue('1900-01-01');
    }

    public function test64bit1900Date(): void
    {
        if (PHP_INT_SIZE !== 8) {
            $this->markTestSkipped('Platform is not 64-bit');
        }

        $type   = Type::getType(Type::DATE_IMMUTABLE);
        $return = $type->convertToDatabaseValue('1900-01-01');

        self::assertInstanceOf(UTCDateTime::class, $return);
        self::assertEquals(new UTCDateTime(strtotime('1900-01-01') * 1000), $return);
    }

    private function assertTimestampEquals(DateTimeImmutable $expected, DateTimeImmutable $actual): void
    {
        self::assertEquals($expected->format('U.u'), $actual->format('U.u'));
    }
}
