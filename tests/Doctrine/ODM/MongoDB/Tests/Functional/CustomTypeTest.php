<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use Exception;

use function array_map;
use function array_values;
use function is_array;

class CustomTypeTest extends BaseTest
{
    public static function setUpBeforeClass(): void
    {
        Type::addType('date_collection', DateCollectionType::class);
    }

    public function testCustomTypeValueConversions(): void
    {
        $country                   = new Country();
        $country->nationalHolidays = [new DateTime(), new DateTime()];

        $this->dm->persist($country);
        $this->dm->flush();

        $this->dm->clear();

        $country = $this->dm->find(Country::class, $country->id);

        self::assertContainsOnly('DateTime', $country->nationalHolidays);
    }

    public function testConvertToDatabaseValueExpectsArray(): void
    {
        $country                   = new Country();
        $country->nationalHolidays = new DateTime();

        $this->dm->persist($country);
        $this->expectException(CustomTypeException::class);
        $this->dm->flush();
    }
}

class DateCollectionType extends Type
{
    use ClosureToPHP;

    /**
     * Method called by PersistenceBuilder
     */
    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            throw new CustomTypeException('Array expected.');
        }

        $converter = Type::getType('date');

        $value = array_map(static fn ($date) => $converter->convertToDatabaseValue($date), array_values($value));

        return $value;
    }

    public function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            throw new CustomTypeException('Array expected.');
        }

        $converter = Type::getType('date');

        $value = array_map(static fn ($date) => $converter->convertToPHPValue($date), array_values($value));

        return $value;
    }

    /**
     * Method never called
     */
    public function closureToMongo(): string
    {
        // todo: microseconds o.O
        return '$return = array_map(function($v) { if ($v instanceof \MongoDB\BSON\UTCDateTime) { $v = $v->getTimestamp(); } else if (is_string($v)) { $v = strtotime($v); } return new \MongoDB\BSON\UTCDateTime($v); }, $value);';
    }
}

class CustomTypeException extends Exception
{
}

/** @ODM\Document */
class Country
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="date_collection")
     *
     * @var DateTime[]|DateTime|null
     */
    public $nationalHolidays;
}
