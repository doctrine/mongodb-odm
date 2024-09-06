Custom Mapping Types
====================

Doctrine allows you to create new mapping types. This can come in
handy when you're missing a specific mapping type or when you want
to replace the existing implementation of a mapping type.

In order to create a new mapping type you need to subclass
``Doctrine\ODM\MongoDB\Types\Type`` and implement/override
the methods.

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

    class DateTimeWithTimezoneType extends Type
    {
        // This trait provides default closureToPHP used during data hydration
        use ClosureToPHP;

        public function convertToPHPValue($value): DateTimeImmutable
        {
            $timeZone = new DateTimeZone($value['tz']);
            $dateTime = $value['utc']
                ->toDateTime()
                ->setTimeZone($timeZone);

            return DateTimeImmutable::createFromMutable($dateTime);
        }

        public function convertToDatabaseValue($value): array
        {
            if (! isset($value['utc'], $value['tz'])) {
                throw new RuntimeException('Database value cannot be converted to date with timezone. Expected array with "utc" and "tz" keys.');
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
know about it:

.. code-block:: php

    <?php

    // in bootstrapping code

    use Doctrine\ODM\MongoDB\Types\Type;

    // Adds a type. This results in an exception if type with given name is already registered
    Type::addType('date_with_timezone', \My\Project\Types\DateTimeWithTimezoneType::class);

    // Overrides a type. This results in an exception if type with given name is not registered
    Type::overrideType('date_immutable', \My\Project\Types\DateTimeWithTimezoneType::class);

    // Registers a type without checking whether it was already registered
    Type::registerType('date_immutable', \My\Project\Types\DateTimeWithTimezoneType::class);

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

.. |FQCN| raw:: html
  <abbr title="Fully-Qualified Class Name">FQCN</abbr>
