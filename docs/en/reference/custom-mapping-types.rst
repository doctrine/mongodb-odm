Custom Mapping Types
====================

Doctrine allows you to create new mapping types. This can come in
handy when you're missing a specific mapping type or when you want
to replace the existing implementation of a mapping type.

In order to create a new mapping type you need to subclass
``Doctrine\ODM\MongoDB\Types\Type`` and implement/override
the methods. Here is an example skeleton of such a custom type
class:

.. code-block:: php

    <?php

    namespace My\Project\Types;

    use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
    use Doctrine\ODM\MongoDB\Types\Type;
    use MongoDB\BSON\UTCDateTime;

    /**
     * My custom datatype.
     */
    class MyType extends Type
    {
        // This trait provides default closureToPHP used during data hydration
        use ClosureToPHP;

        public function convertToPHPValue($value): \DateTime
        {
            // This is called to convert a Mongo value to a PHP representation
            return new \DateTime('@' . $value->sec);
        }

        public function convertToDatabaseValue($value): UTCDateTime
        {
            // This is called to convert a PHP value to its Mongo equivalent
            return new UTCDateTime($value);
        }
    }

Restrictions to keep in mind:

-
   If the value of the field is *NULL* the method
   ``convertToDatabaseValue()`` is not called.
-
   The ``UnitOfWork`` never passes values to the database convert
   method that did not change in the request.

When you have implemented the type you still need to let Doctrine
know about it:

.. code-block:: php

    <?php

    // in bootstrapping code

    // ...

    use Doctrine\ODM\MongoDB\Types\Type;

    // ...

    // Adds a type. This results in an exception if type with given name is already registered
    Type::addType('mytype', \My\Project\Types\MyType::class);

    // Overrides a type. This results in an exception if type with given name is not registered
    Type::overrideType('mytype', \My\Project\Types\MyType::class);

    // Registers a type without checking whether it was already registered
    Type::registerType('mytype', \My\Project\Types\MyType::class);

As can be seen above, when registering the custom types in the
configuration you specify a unique name for the mapping type and
map that to the corresponding |FQCN|. Now you can use your new
type in your mapping like this:

.. configuration-block::

    .. code-block:: php

        <?php

        class MyPersistentClass
        {
            #[Field(type: 'mytype')]
            private $field;
        }

    .. code-block:: xml

        <field field-name="field" type="mytype" />
