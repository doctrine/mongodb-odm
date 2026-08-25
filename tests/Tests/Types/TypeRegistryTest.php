<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\FloatType;
use Doctrine\ODM\MongoDB\Types\IntType;
use Doctrine\ODM\MongoDB\Types\InvalidTypeException;
use Doctrine\ODM\MongoDB\Types\RawType;
use Doctrine\ODM\MongoDB\Types\StringType;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

use function iterator_to_array;

class TypeRegistryTest extends BaseTestCase
{
    public function testBuiltInTypesAreAvailable(): void
    {
        $registry = new TypeRegistry();

        self::assertTrue($registry->has(Type::STRING));
        self::assertInstanceOf(StringType::class, $registry->get(Type::STRING));
        self::assertSame($registry->get(Type::STRING), $registry->get(Type::STRING));
    }

    public function testInvalidType(): void
    {
        $registry = new TypeRegistry();

        self::expectException(InvalidTypeException::class);
        self::expectExceptionMessage('Invalid type specified: "foo"');

        $registry->get('foo');
    }

    public function testRegister(): void
    {
        $registry = new TypeRegistry();
        self::assertFalse($registry->has('my_custom_type'));

        $registry->register('my_custom_type', IntType::class);
        self::assertTrue($registry->has('my_custom_type'));
        self::assertInstanceOf(IntType::class, $registry->get('my_custom_type'));
        self::assertSame($registry->get('my_custom_type'), $registry->get('my_custom_type'));

        $registry->register('my_custom_type', RawType::class);
        self::assertInstanceOf(RawType::class, $registry->get('my_custom_type'));
    }

    public function testRegisterTypeInstance(): void
    {
        $registry = new TypeRegistry();
        self::assertFalse($registry->has('my_custom_type'));

        $typeInstance = new IntType();
        $registry->register('my_custom_type', $typeInstance);
        self::assertTrue($registry->has('my_custom_type'));
        self::assertSame($typeInstance, $registry->get('my_custom_type'));

        // Replacing it with a type by class name unsets the instance
        $registry->register('my_custom_type', FloatType::class);
        self::assertInstanceOf(FloatType::class, $registry->get('my_custom_type'));
    }

    public function testRegisterOverridesABuiltInType(): void
    {
        $registry = new TypeRegistry();
        $registry->register(Type::STRING, new RawType());

        self::assertInstanceOf(RawType::class, $registry->get(Type::STRING));
    }

    public function testSharedInstance(): void
    {
        self::assertSame(TypeRegistry::getSharedInstance(), TypeRegistry::getSharedInstance());
    }

    public function testRegisterRequiresATypeClassOrInstance(): void
    {
        $registry = new TypeRegistry();
        self::expectException(InvalidArgumentException::class);
        // @phpstan-ignore argument.type
        $registry->register('invalid_type', stdClass::class);
    }

    public function testRegisterRejectsClassWithRequiredConstructorParameters(): void
    {
        $registry = new TypeRegistry();
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('must not have a constructor with required parameters');
        $registry->register('my_type', TypeWithRequiredConstructor::class);
    }

    public function testRegisterAllowsClassWithInheritedNoArgConstructor(): void
    {
        $registry = new TypeRegistry();
        $registry->register('my_type', TypeWithInheritedConstructor::class);
        self::assertInstanceOf(TypeWithInheritedConstructor::class, $registry->get('my_type'));
    }

    public function testConstructorAcceptsInstancesAndClassNames(): void
    {
        $instance = new RawType();
        $registry = new TypeRegistry([
            'by_instance' => $instance,
            'by_class' => FloatType::class,
            Type::STRING => IntType::class,
        ]);

        self::assertSame($instance, $registry->get('by_instance'));
        self::assertInstanceOf(FloatType::class, $registry->get('by_class'));
        self::assertInstanceOf(IntType::class, $registry->get(Type::STRING), 'Built-in types can be overridden');
    }

    public function testConstructorRejectsServiceIdsWithoutContainer(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('can only be used together with a "Psr\Container\ContainerInterface"');

        new TypeRegistry([], ['my_type' => 'app.type.my_type']);
    }

    public function testConstructorRequiresServiceIdsWithContainer(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('is required when passing a "Psr\Container\ContainerInterface"');

        new TypeRegistry(self::createStub(ContainerInterface::class));
    }

    public function testTheContainerIsNotQueriedByHas(): void
    {
        $container = new InMemoryContainer(['app.type.my_type' => static fn (): RawType => self::fail('The service must not be resolved')]);
        $registry  = new TypeRegistry($container, ['my_type' => 'app.type.my_type']);

        self::assertTrue($registry->has('my_type'));
    }

    public function testTheResolvedServiceIsCached(): void
    {
        $container = new InMemoryContainer(['app.type.my_type' => static fn (): RawType => new RawType()]);
        $registry  = new TypeRegistry($container, ['my_type' => 'app.type.my_type']);

        self::assertSame($registry->get('my_type'), $registry->get('my_type'));
    }

    public function testContainerBackedTypeCanOverrideABuiltInType(): void
    {
        $instance  = new RawType();
        $container = new InMemoryContainer(['app.type.string' => static fn (): RawType => $instance]);
        $registry  = new TypeRegistry($container, [Type::STRING => 'app.type.string']);

        self::assertSame($instance, $registry->get(Type::STRING));
    }

    public function testRegisterDiscardsAnUnresolvedServiceId(): void
    {
        $container = new InMemoryContainer(['app.type.my_type' => static fn (): RawType => self::fail('The service must not be resolved')]);
        $registry  = new TypeRegistry($container, ['my_type' => 'app.type.my_type']);

        $registry->register('my_type', FloatType::class);

        self::assertInstanceOf(FloatType::class, $registry->get('my_type'));
    }

    public function testUnknownServiceIsReportedAsAnUnknownType(): void
    {
        $registry = new TypeRegistry(new InMemoryContainer([]), ['my_type' => 'app.type.missing']);

        self::expectException(InvalidTypeException::class);
        self::expectExceptionMessage('Service "app.type.missing" registered for type "my_type" was not found in the container.');

        $registry->get('my_type');
    }

    public function testServiceMustResolveToAType(): void
    {
        $registry = new TypeRegistry(
            new InMemoryContainer(['app.type.my_type' => static fn (): stdClass => new stdClass()]),
            ['my_type' => 'app.type.my_type'],
        );

        self::expectException(InvalidTypeException::class);
        self::expectExceptionMessage('must be an instance of "Doctrine\ODM\MongoDB\Types\Type", got "stdClass"');

        $registry->get('my_type');
    }

    public function testIteratorYieldsEveryTypeOnce(): void
    {
        $instance = new RawType();
        $registry = new TypeRegistry(
            new InMemoryContainer(['app.type.my_type' => static fn (): RawType => $instance]),
            ['my_type' => 'app.type.my_type', Type::STRING => 'app.type.my_type'],
        );
        $registry->register('other_type', FloatType::class);

        $types = iterator_to_array($registry);

        self::assertSame($instance, $types['my_type']);
        self::assertSame($instance, $types[Type::STRING], 'A service may back several type names');
        self::assertInstanceOf(FloatType::class, $types['other_type']);
        self::assertInstanceOf(IntType::class, $types[Type::INT], 'Built-in types are yielded too');
        self::assertCount(2 + 1 + 30 - 1, $types, 'Overridden built-in names are yielded once');
    }

    public function testIteratingPartiallyDoesNotResolveTheRemainingTypes(): void
    {
        $registry = new TypeRegistry(
            new InMemoryContainer(['app.type.my_type' => static fn (): RawType => self::fail('The service must not be resolved')]),
            ['my_type' => 'app.type.my_type'],
        );
        $registry->register('other_type', new FloatType());

        foreach ($registry as $name => $type) {
            self::assertSame('other_type', $name);

            break;
        }
    }
}

class TypeWithRequiredConstructor extends IntType
{
    public function __construct(private string $required)
    {
    }
}

class TypeWithInheritedConstructor extends IntType
{
}

final class InMemoryContainer implements ContainerInterface
{
    /** @param array<string, callable(): mixed> $factories */
    public function __construct(private array $factories)
    {
    }

    public function get(string $id): mixed
    {
        if (! isset($this->factories[$id])) {
            throw new class ('Service "' . $id . '" not found.') extends InvalidArgumentException implements NotFoundExceptionInterface, ContainerExceptionInterface {
            };
        }

        return ($this->factories[$id])();
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }
}
