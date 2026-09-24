<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\FloatType;
use Doctrine\ODM\MongoDB\Types\IntType;
use Doctrine\ODM\MongoDB\Types\InvalidTypeException;
use Doctrine\ODM\MongoDB\Types\RawType;
use Doctrine\ODM\MongoDB\Types\StringType;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_combine;
use function array_fill;
use function array_keys;
use function count;
use function iterator_to_array;

class TypeRegistryTest extends TestCase
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
        self::assertSame(TypeRegistry::getDeprecatedSharedInstance(), TypeRegistry::getDeprecatedSharedInstance());
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

    public function testRegisterRejectsAbstractClass(): void
    {
        $registry = new TypeRegistry();
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('is not instantiable');
        $registry->register('my_type', AbstractType::class);
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

    public function testProviderBackedTypeIsReportedByHas(): void
    {
        $provider = new InMemoryServiceProvider(['my_type' => static fn (): RawType => self::fail('The service must not be resolved')]);
        $registry = new TypeRegistry($provider);

        self::assertTrue($registry->has('my_type'));
    }

    public function testServiceIsResolvedFromTheProvider(): void
    {
        $instance = new RawType();
        $provider = new InMemoryServiceProvider(['my_type' => static fn (): RawType => $instance]);
        $registry = new TypeRegistry($provider);

        // The registry does not cache service types: instance identity is governed by the
        // provider (shared service semantics), not by the registry.
        self::assertSame($instance, $registry->get('my_type'));
    }

    public function testServiceTypeIsResolvedOnEachCall(): void
    {
        $provider = new InMemoryServiceProvider(['my_type' => static fn (): RawType => new RawType()]);
        $registry = new TypeRegistry($provider);

        // The registry never memoizes the service branch, so a provider returning a fresh
        // instance each call yields distinct instances, mirroring "shared: false" semantics.
        self::assertNotSame($registry->get('my_type'), $registry->get('my_type'));
    }

    public function testProviderBackedTypeCanOverrideABuiltInType(): void
    {
        $instance = new RawType();
        $provider = new InMemoryServiceProvider([Type::STRING => static fn (): RawType => $instance]);
        $registry = new TypeRegistry($provider);

        self::assertSame($instance, $registry->get(Type::STRING));
    }

    public function testRegisterOverridesAnUnresolvedProviderBackedType(): void
    {
        $provider = new InMemoryServiceProvider(['my_type' => static fn (): RawType => self::fail('The service must not be resolved')]);
        $registry = new TypeRegistry($provider);

        $registry->register('my_type', FloatType::class);

        self::assertInstanceOf(FloatType::class, $registry->get('my_type'));
    }

    public function testUnknownServicePropagatesTheProviderException(): void
    {
        $registry = new TypeRegistry(new InMemoryServiceProvider([
            'my_type' => static function (): never {
                throw new class extends InvalidArgumentException implements NotFoundExceptionInterface, ContainerExceptionInterface {
                };
            },
        ]));

        // The registry delegates to the provider without translating container exceptions, so
        // a missing or failing service bubbles up raised by the ServiceProviderInterface.
        self::expectException(NotFoundExceptionInterface::class);

        $registry->get('my_type');
    }

    public function testServiceMustResolveToAType(): void
    {
        $registry = new TypeRegistry(
            new InMemoryServiceProvider(['my_type' => static fn (): stdClass => new stdClass()]),
        );

        self::expectException(InvalidTypeException::class);
        self::expectExceptionMessage('must be an instance of "Doctrine\ODM\MongoDB\Types\Type", got "stdClass"');

        $registry->get('my_type');
    }

    public function testIteratorYieldsEveryTypeOnce(): void
    {
        $instance = new RawType();
        $registry = new TypeRegistry(
            new InMemoryServiceProvider(['my_type' => static fn (): RawType => $instance]),
        );
        $registry->register('other_type', FloatType::class);

        $types = iterator_to_array($registry);

        self::assertSame($instance, $types['my_type']);
        self::assertInstanceOf(FloatType::class, $types['other_type']);
        self::assertInstanceOf(IntType::class, $types[Type::INT], 'Built-in types are yielded too');
        self::assertCount(1 + 1 + 30, $types, 'Provider-backed and built-in types are yielded once');
    }

    public function testIteratingDoesNotResolveTheLazyService(): void
    {
        $registry = new TypeRegistry(
            new InMemoryServiceProvider(['my_type' => static fn (): RawType => self::fail('The service must not be resolved')]),
        );
        $registry->register('other_type', new FloatType());

        // Built-in types and registered instances are yielded before the provider-backed type,
        // so breaking early must resolve neither the instance nor the lazy service.
        foreach ($registry as $name => $type) {
            if ($name !== 'other_type') {
                continue;
            }

            self::assertInstanceOf(FloatType::class, $type);

            return;
        }

        self::fail('The registered instance should be yielded before the lazy service.');
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

abstract class AbstractType extends Type
{
}

/** @implements ServiceProviderInterface<mixed> */
final class InMemoryServiceProvider implements ServiceProviderInterface
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

    public function getProvidedServices(): array
    {
        return array_combine(
            array_keys($this->factories),
            array_fill(0, count($this->factories), Type::class),
        );
    }
}
