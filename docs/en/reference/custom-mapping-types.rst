Custom Mapping Types
====================

Doctrine allows you to create new mapping types. This can come in
handy when you're missing a specific mapping type or when you want
to replace the existing implementation of a mapping type.

In order to create a new mapping type you need to subclass
``Doctrine\ODM\MongoDB\Types\Type`` and implement/override
the methods.

Date Example: Mapping DateTimeImmutable with Timezone
-----------------------------------------------------

The following example defines a custom type that stores ``DateTimeInterface``
instances as an embedded document containing a BSON date and accompanying
timezone string. Those same embedded documents are then be translated back into
a ``DateTimeImmutable`` when the data is read from the database.

.. code-block:: php

    <?php

    namespace My\Project\Types;

    use DateTimeImmutable;
    use DateTimeZone;
    use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
    use Doctrine\ODM\MongoDB\Types\Type;
    use MongoDB\BSON\UTCDateTime;
    use RuntimeException;

    class DateTimeWithTimezoneType extends Type
    {
        // This trait provides default closureToPHP used during data hydration
        use ClosureToPHP;

        /** @param array{utc: UTCDateTime, tz: string} $value */
        public function convertToPHPValue($value): DateTimeImmutable
        {
            if (!isset($value['utc'], $value['tz'])) {
                throw new RuntimeException('Database value cannot be converted to date with timezone. Expected array with "utc" and "tz" keys.');
            }

            $timeZone = new DateTimeZone($value['tz']);
            $dateTime = $value['utc']
                ->toDateTime()
                ->setTimeZone($timeZone);

            return DateTimeImmutable::createFromMutable($dateTime);
        }

        /** @return array{utc: UTCDateTime, tz: string} */
        public function convertToDatabaseValue($value): array
        {
            if (!$value instanceof DateTimeImmutable) {
                throw new \RuntimeException(
                    sprintf(
                        'Expected instance of \DateTimeImmutable, got %s',
                        gettype($value)
                    )
                );
            }

            return [
                'utc' => new UTCDateTime($value),
                'tz' => $value->getTimezone()->getName(),
            ];
        }
    }

Restrictions to keep in mind:

-
   If the value of the field is *NULL* the method ``convertToDatabaseValue()``
   is not called. You don't need to check for *NULL* values.
-
   The ``UnitOfWork`` never passes values to the database convert
   method that did not change in the request.

When you have implemented the type you still need to let Doctrine
know about it. You can create a ``TypeRegistry`` and inject it into the
``Configuration``:

.. code-block:: php

    <?php

    // in bootstrapping code

    use Doctrine\ODM\MongoDB\Configuration;
    use Doctrine\ODM\MongoDB\Types\TypeRegistry;

    // A registry always contains all built-in types. Types passed to the constructor are
    // registered on top and override the built-in type of the same name.
    $typeRegistry = new TypeRegistry([
        'date_with_timezone' => new \My\Project\Types\DateTimeWithTimezoneType(),
        'date_immutable' => new \My\Project\Types\DateTimeWithTimezoneType(),
    ]);

    // Types can also be added afterwards, by instance or by class name.
    // Registering an existing name replaces the type, it does not throw.
    $typeRegistry->register('date_with_timezone', \My\Project\Types\DateTimeWithTimezoneType::class);

    $config = new Configuration();
    $config->setTypeProvider($typeRegistry);

Types registered this way are scoped to the ``DocumentManager`` instances using that
``Configuration``, so they cannot affect the rest of the application. The registry can
only be set once on a ``Configuration``, before any metadata is loaded.

``Configuration`` accepts any ``Doctrine\ODM\MongoDB\Types\TypeProvider``, so
``TypeRegistry`` can be replaced with your own implementation. A provider only has to
answer ``get()`` and ``has()``, and to yield its types when iterated:

.. code-block:: php

    <?php

    foreach ($dm->getConfiguration()->getTypeProvider() as $typeName => $type) {
        // ...
    }

Lazy-loading types from a container
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When types have their own dependencies, instantiating them all up front is wasteful.
Pass a service provider to resolve each type on first use and then cache it:

.. code-block:: php

    <?php

    // $container implements Symfony\Contracts\Service\ServiceProviderInterface
    $typeRegistry = new TypeRegistry($container);

The service ID is the type name, and the provider lists the types it exposes through
``getProvidedServices()``. The container is not queried during construction, and
``has()`` only asks it whether a service exists, without resolving it. Iterating the
registry does resolve every type.

``ServiceProviderInterface`` is the PSR-11 container interface extended with that one
method, so a Symfony service locator can be passed as-is.

As can be seen above, when registering the custom types in the configuration you
specify a unique name for the mapping type and map that to the corresponding
|FQCN|. Now you can use your new type in your mapping like this:

.. configuration-block::

    .. code-block:: php

        <?php

        use DateTimeImmutable;

        class Thing
        {
            #[Field(type: 'date_with_timezone')]
            public DateTimeImmutable $date;
        }

    .. code-block:: xml

        <field field-name="field" type="date_with_timezone" />

Custom Type Example: Mapping a Money Value Object
-------------------------------------------------

You can create a custom mapping type for your own value objects or classes. For
example, to map a ``Money`` value object using the `moneyphp/money library`_, you can
implement a type that converts between this class and a BSON embedded document format.

This approach works for any custom class by adapting the conversion logic to your needs.

Example Implementation (using ``Money\Money``)::

.. code-block:: php

    <?php

    namespace App\MongoDB\Types;

    use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
    use Doctrine\ODM\MongoDB\Types\Type;
    use InvalidArgumentException;
    use Money\Money;
    use Money\Currency;

    final class MoneyType extends Type
    {
        // This trait provides a default closureToPHP used during data hydration
        use ClosureToPHP;

        public function convertToPHPValue(mixed $value): ?Money
        {
            if (null === $value) {
                return null;
            }

            if (is_array($value) && isset($value['amount'], $value['currency'])) {
                return new Money($value['amount'], new Currency($value['currency']));
            }

            throw new InvalidArgumentException(sprintf('Could not convert database value from "%s" to %s', get_debug_type($value), Money::class));
        }

        public function convertToDatabaseValue(mixed $value): ?array
        {
            if (null === $value) {
                return null;
            }

            if ($value instanceof Money) {
                return [
                    'amount' => $value->getAmount(),
                    'currency' => $value->getCurrency()->getCode(),
                ];
            }

            throw new InvalidArgumentException(sprintf('Could not convert database value from "%s" to array', get_debug_type($value)));
        }
    }

Register the type in your bootstrap code::

.. code-block:: php

    $typeRegistry = new TypeRegistry([
        Money::class => new App\MongoDB\Types\MoneyType(),
    ]);

By using the |FQCN| of the value object class as the type name, the type is
automatically used when encountering a property of that class. This means you
can omit the ``type`` option when defining the field mapping::

.. code-block:: php

    #[Field]
    public ?\Money\Money $price;

.. note::

    This implementation of ``MoneyType`` is kept simple for illustration purposes
    and does not handle all edge cases, but it should give you a good starting
    point for implementing your own custom types.

.. _`moneyphp/money library`: https://github.com/moneyphp/money
.. |FQCN| raw:: html
  <abbr title="Fully-Qualified Class Name">FQCN</abbr>
