<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tests\CaptureDeprecationMessages;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\EquatableType;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeGuesser;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use Exception;
use InvalidArgumentException;

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

        $this->typeRegistry->register('date_collection', new DateCollectionType());
        $this->typeRegistry->register(Language::class, LanguageType::class);
        $this->typeRegistry->register('custom_value_object_child', CustomValueObjectChild::class);
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

    public function testValueObjectChangeSets(): void
    {
        $root        = new DocumentWithCustomTypeValueObject();
        $root->child = new ValueObjectChild(10, 12);

        $this->uow->persist($root);

        $this->uow->computeChangeSets();

        self::assertNotEmpty($this->uow->getDocumentChangeSet($root));

        $this->uow->commit();

        // Replacing the value object with an equal one is not a change.
        $root->child = new ValueObjectChild(10, 12);
        $this->uow->computeChangeSets();
        self::assertArrayNotHasKey('child', $this->uow->getDocumentChangeSet($root));

        // Replacing the value object with a different one is a change.
        $root->child = new ValueObjectChild(12, 12);
        $this->uow->computeChangeSets();
        $changeSet = $this->uow->getDocumentChangeSet($root);
        self::assertArrayHasKey('child', $changeSet);
        self::assertSame(12, $changeSet['child'][1]->prop1);
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

    public function testNotOverridingClosureToPHPIsDeprecated(): void
    {
        $type = new CustomTypeWithoutClosureToPHP();

        $code = $this->captureDeprecationMessages(static fn () => $type->closureToPHP(), $deprecations);

        self::assertSame('$return = $value;', $code);
        self::assertSame(['Since doctrine/mongodb-odm 2.16: The method Type::closureToPHP() will change its default implementation in 3.0 to use convertToPHPValue(). Override this method if you need custom behavior before upgrading to 3.0 or use the trait ClosureToPHP to get the upcoming behavior now.'], $deprecations);
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

        $registry  = new TypeRegistry();
        $converter = $registry->get('date');

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

        $registry  = new TypeRegistry();
        $converter = $registry->get('date');

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

class CustomValueObjectChild extends Type implements EquatableType
{
    use ClosureToPHP;

    /** @return array{prop1:int,prop2:int}|null */
    public function convertToDatabaseValue($value): ?array
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof ValueObjectChild);

        return ['prop1' => $value->prop1, 'prop2' => $value->prop2];
    }

    /** @param array{prop1:int,prop2:int}|null $value */
    public function convertToPHPValue($value): ?ValueObjectChild
    {
        if ($value === null) {
            return null;
        }

        assert(is_array($value) && isset($value['prop1'], $value['prop2']));

        return new ValueObjectChild($value['prop1'], $value['prop2']);
    }

    public function valuesAreEqual(object $a, object $b): bool
    {
        return $a instanceof ValueObjectChild
            && $b instanceof ValueObjectChild
            && $a->prop1 === $b->prop1
            && $a->prop2 === $b->prop2;
    }
}

class ValueObjectChild
{
    public function __construct(
        public readonly int $prop1,
        public readonly int $prop2,
    ) {
    }
}

#[ODM\Document]
class DocumentWithCustomTypeValueObject
{
    #[ODM\Id]
    public ?string $id;

    #[ODM\Field(type: 'custom_value_object_child')]
    public ValueObjectChild $child;
}
