<?php

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\Type;

class DateTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDateTime()
    {
        $type = Type::getType(Type::DATE);

        $timestamp = 100000000.001;
        $dateTime = $type->getDateTime($timestamp);
        $this->assertEquals($timestamp, $dateTime->format('U.u'));

        $mongoDate = new \MongoDate(100000000, 1000);
        $dateTime = $type->getDateTime($mongoDate);
        $this->assertEquals($timestamp, $dateTime->format('U.u'));
    }

    public function testConvertToDatabaseValue()
    {
        $type = Type::getType(Type::DATE);

        $this->assertNull($type->convertToDatabaseValue(null), 'null is not converted');

        $mongoDate = new \MongoDate();
        $this->assertSame($mongoDate, $type->convertToDatabaseValue($mongoDate), 'MongoDate objects are not converted');

        $timestamp = 100000000.123;
        $dateTime = \DateTime::createFromFormat('U.u', $timestamp);
        $mongoDate = new \MongoDate(100000000, 123000);
        $this->assertEquals($mongoDate, $type->convertToDatabaseValue($dateTime), 'DateTime objects are converted to MongoDate objects');
        $this->assertEquals($mongoDate, $type->convertToDatabaseValue($timestamp), 'Numeric timestamps are converted to MongoDate objects');
        $this->assertEquals($mongoDate, $type->convertToDatabaseValue('' . $timestamp), 'String dates are converted to MongoDate objects');
        $this->assertEquals($mongoDate, $type->convertToDatabaseValue($mongoDate), 'MongoDate objects are converted to MongoDate objects');
        $this->assertEquals(null, $type->convertToDatabaseValue(null), 'null are converted to null');
    }

    public function testConvertDateTimeImmutable()
    {
        if (! class_exists('DateTimeImmutable')) {
            $this->markTestSkipped('DateTimeImmutable class does not exist in your PHP version');
        }

        $type = Type::getType(Type::DATE);

        $timestamp = 100000000.123;
        $mongoDate = new \MongoDate(100000000, 123000);

        $dateTimeImmutable = \DateTimeImmutable::createFromFormat('U.u', $timestamp);
        $this->assertEquals($mongoDate, $type->convertToDatabaseValue($dateTimeImmutable), 'DateTimeImmutable objects are converted to MongoDate objects');
    }

    public function testConvertOldDate()
    {
        $type = Type::getType(Type::DATE);

        $date = new \DateTime('1900-01-01 00:00:00.123', new \DateTimeZone('UTC'));
        $timestamp = "-2208988800.123";
        $this->assertEquals($type->convertToDatabaseValue($timestamp), $type->convertToDatabaseValue($date));
    }

    /**
     * @dataProvider provideInvalidDateValues
     * @expectedException InvalidArgumentException
     */
    public function testConvertToDatabaseValueWithInvalidValues($value)
    {
        $type = Type::getType(Type::DATE);
        $type->convertToDatabaseValue($value);
    }

    public function provideInvalidDateValues()
    {
        return array(
            'array'  => array(array()),
            'string' => array('whatever'),
            'bool'   => array(false),
            'object' => array(new \stdClass()),
            'invalid string' => array('foo'),
        );
    }

    /**
     * @dataProvider provideDatabaseToPHPValues
     */
    public function testConvertToPHPValue($input, $output)
    {
        $type = Type::getType(Type::DATE);
        $return = $type->convertToPHPValue($input);

        $this->assertInstanceOf('DateTime', $return);
        $this->assertTimestampEquals($output, $return);
    }

    public function testConvertToPHPValueDoesNotConvertNull()
    {
        $type = Type::getType(Type::DATE);

        $this->assertNull($type->convertToPHPValue(null));
    }

    /**
     * @dataProvider provideDatabaseToPHPValues
     */
    public function testClosureToPHP($input, $output)
    {
        $type = Type::getType(Type::DATE);
        $return = null;

        call_user_func(function($value) use ($type, &$return) {
            eval($type->closureToPHP());
        }, $input);

        $this->assertInstanceOf('DateTime', $return);
        $this->assertTimestampEquals($output, $return);
    }

    public function provideDatabaseToPHPValues()
    {
        $yesterday = strtotime('yesterday');
        $mongoDate = new \MongoDate($yesterday);
        $dateTime = new \DateTime('@' . $yesterday);

        return array(
            array($dateTime, $dateTime),
            array($mongoDate, $dateTime),
            array($yesterday, $dateTime),
            array(date('c', $yesterday), $dateTime),
            array(new \MongoDate(100000000, 123000), \DateTime::createFromFormat('U.u', '100000000.123')),
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test32bit1900Date()
    {
        if (PHP_INT_SIZE === 4) {
            $type = Type::getType(Type::DATE);
            $type->convertToDatabaseValue('1900-01-01');
        } else {
            $this->markTestSkipped("Platform is not 32-bit");
        }
    }

    public function test64bit1900Date()
    {
        if (PHP_INT_SIZE === 8) {
            $type = Type::getType(Type::DATE);
            $return = $type->convertToDatabaseValue('1900-01-01');

            $this->assertInstanceOf('MongoDate', $return);
            $this->assertEquals(new \MongoDate(strtotime('1900-01-01')), $return);
        } else {
            $this->markTestSkipped("Platform is not 64-bit");
        }
    }

    private function assertTimestampEquals(\DateTime $expected, \DateTime $actual)
    {
        $this->assertEquals($expected->format('U.u'), $actual->format('U.u'));
    }
}
