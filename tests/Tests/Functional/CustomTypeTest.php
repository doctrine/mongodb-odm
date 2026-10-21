<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use DateTimeInterface;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeGuesser;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use Exception;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;

use function array_map;
use function array_values;
use function assert;
use function is_array;

class CustomTypeTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->typeRegistry->register('date_collection', new DateCollectionType());
        $this->typeRegistry->register(Language::class, LanguageType::class);
        $this->typeRegistry->register('custom_type_without_closure_to_php', CustomTypeWithoutClosureToPHP::class);
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

    public function testConvertToDatabaseValue(): void
    {
        $lang    = new Language('French', 'fr');
        $guesser = new TypeGuesser($this->typeRegistry);
        $type    = $guesser->guessTypeFromValue($lang);
        self::assertInstanceOf(LanguageType::class, $type);

        $databaseValue = $guesser->convertToDatabaseValue($lang);
        self::assertSame(['name' => 'French', 'code' => 'fr'], $databaseValue);
    }

    public function testNoArgConstructorAllowedInCustomTypeRegisteredByClassName(): void
    {
        $this->typeRegistry->register('custom_with_constructor', CustomTypeWithConstructor::class);

        self::assertInstanceOf(CustomTypeWithConstructor::class, $this->typeRegistry->get('custom_with_constructor'));
    }

    public function testRequiredConstructorParametersNotAllowedInCustomTypeRegisteredByClassName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type class "Doctrine\ODM\MongoDB\Tests\Functional\CustomTypeWithRequiredConstructor" must not have a constructor with required parameters to be registered by class name. Register an instance of the class instead.');

        $this->typeRegistry->register('custom_with_required_constructor', CustomTypeWithRequiredConstructor::class);
    }
}

class DateCollectionType extends Type
{
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

        $registry  = new TypeRegistry();
        $converter = $registry->get('date');

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

        $registry  = new TypeRegistry();
        $converter = $registry->get('date');

        $value = array_map(static fn ($date) => $converter->convertToPHPValue($date), array_values($value));

        return $value;
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
    /** @return array{name:string,code:string}|null */
    public function convertToDatabaseValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof Language);

        return ['name' => $value->name, 'code' => $value->code];
    }

    /** @param array{name:string,code:string}|null $value */
    public function convertToPHPValue(mixed $value): ?Language
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

class CustomTypeWithConstructor extends Type
{
    public function __construct()
    {
    }
}

class CustomTypeWithRequiredConstructor extends Type
{
    public function __construct(private string $required)
    {
    }
}
