<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use DateTimeInterface;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tests\CaptureDeprecationMessages;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use Exception;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\After;
use ReflectionProperty;

use function array_map;
use function array_values;
use function assert;
use function is_array;

class CustomTypeTest extends BaseTestCase
{
    use CaptureDeprecationMessages;

    public function setUp(): void
    {
        parent::setUp();

        Type::addType('date_collection', DateCollectionType::class);
        Type::addType(Language::class, LanguageType::class);
        Type::addType('custom_type_without_closure_to_php', CustomTypeWithoutClosureToPHP::class);
    }

    #[After]
    public function restoreTypeMap(): void
    {
        $r = new ReflectionProperty(Type::class, 'typesMap');
        $r->setValue(null, $r->getDefaultValue());
    }

    public function testCustomTypeValueConversions(): void
    {
        $country                   = new Country();
        $country->nationalHolidays = [new DateTime(), new DateTime()];

        $this->dm->persist($country);
        $this->dm->flush();

        $this->dm->clear();

        $country = $this->dm->find(Country::class, $country->id);

        self::assertContainsOnlyInstancesOf(DateTime::class, $country->nationalHolidays);
    }

    public function testConvertToDatabaseValueExpectsArray(): void
    {
        $country                   = new Country();
        $country->nationalHolidays = new DateTime();

        $this->dm->persist($country);
        $this->expectException(CustomTypeException::class);
        $this->dm->flush();
    }

    public function testCustomTypeDetection(): void
    {
        $typeOfField = $this->dm->getClassMetadata(Country::class)->getTypeOfField('lang');
        self::assertSame(Language::class, $typeOfField, 'The custom type should be detected on the field');

        $country       = new Country();
        $country->lang = new Language('French', 'fr');

        $this->dm->persist($country);
        $this->dm->flush();
        $this->dm->clear();

        $country = $this->dm->find(Country::class, $country->id);

        self::assertNotNull($country);
        self::assertInstanceOf(Language::class, $country->lang);
        self::assertSame('French', $country->lang->name);
        self::assertSame('fr', $country->lang->code);
    }

    public function testTypeFromPHPVariable(): void
    {
        $lang = new Language('French', 'fr');
        $type = Type::getTypeFromPHPVariable($lang);
        self::assertInstanceOf(LanguageType::class, $type);

        $databaseValue = Type::convertPHPToDatabaseValue($lang);
        self::assertSame(['name' => 'French', 'code' => 'fr'], $databaseValue);
    }

    public function testNotOverridingClosureToPHPIsDeprecated(): void
    {
        $type = Type::getType('custom_type_without_closure_to_php');

        $code = $this->captureDeprecationMessages(static fn () => $type->closureToPHP(), $deprecations);

        self::assertSame('$return = $value;', $code);
        self::assertSame(['Since doctrine/mongodb-odm 2.16: The method Type::closureToPHP() will change its default implementation in 3.0 to use convertToPHPValue(). Override this method if you need custom behavior before upgrading to 3.0 or use the trait ClosureToPHP to get the upcoming behavior now.'], $deprecations);
    }
}

class DateCollectionType extends Type
{
    use ClosureToPHP;

    /**
     * Method called by PersistenceBuilder
     *
     * @return UTCDateTime[]
     */
    public function convertToDatabaseValue(mixed $value): ?array
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

    /** @return DateTimeInterface[] */
    public function convertToPHPValue(mixed $value): ?array
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

#[ODM\Document]
class Country
{
    #[ODM\Id]
    public ?string $id;

    /** @var DateTime[]|DateTime|null */
    #[ODM\Field(type: 'date_collection')]
    public $nationalHolidays;

    /** The field type is detected from the property type */
    #[ODM\Field(/* type: Language::class */)]
    public ?Language $lang;
}

class Language
{
    public function __construct(
        public string $name,
        public string $code,
    ) {
    }
}

class LanguageType extends Type
{
    use ClosureToPHP;

    /** @return array{name:string,code:string}|null */
    public function convertToDatabaseValue($value): ?array
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof Language);

        return ['name' => $value->name, 'code' => $value->code];
    }

    /** @param array{name:string,code:string}|null $value */
    public function convertToPHPValue($value): ?Language
    {
        if ($value === null) {
            return null;
        }

        assert(is_array($value) && isset($value['name'], $value['code']));

        return new Language($value['name'], $value['code']);
    }
}

class CustomTypeWithoutClosureToPHP extends Type
{
}
