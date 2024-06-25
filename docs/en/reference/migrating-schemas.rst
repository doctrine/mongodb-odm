Migrating Schemas
=================

Even though MongoDB is schemaless, introducing some kind of object mapper means
that your object definitions become your schema. You may have a situation where
you rename a property in your object model but need to load values from older
documents where the field is still using the former name. While you could use
MongoDB's `$rename`_ operator to migrate everything, sometimes a lazy migration
is preferable. Doctrine offers a few different methods for dealing with this
problem!

.. note::

    The features in this chapter were inspired by `Objectify`_, an object mapper
    for the Google App Engine datastore. Additional information may be found in
    the `Objectify schema migration`_ documentation.

Renaming a Field
----------------

Let's say you have a simple document that starts off with the following fields:

.. code-block:: php

    <?php

    #[Document]
    class Person
    {
        #[Id]
        public string $id;

        #[Field(type: 'string')]
        public string $name;
    }

Later on, you need rename ``name`` to ``fullName``; however, you'd like to
hydrate ``fullName`` from ``name`` if the new field doesn't exist.

.. code-block:: php

    <?php

    #[Document]
    class Person
    {
        #[Id]
        public string $id;

        #[Field(type: 'string')]
        #[AlsoLoad('name')]
        public string $fullName;
    }

When a Person is loaded, the ``fullName`` field will be populated with the value
of ``name`` if ``fullName`` is not found. When the Person is persisted, this
value will then be stored in the ``fullName`` field.

.. caution::

    A caveat of this feature is that it only affects hydration. Queries will not
    know about the rename, so a query on ``fullName`` will only match documents
    with the new field name. You can still query using the ``name`` field to
    find older documents. The `$or`_ query operator could be used to match both.

Transforming Data
-----------------

You may have a situation where you want to migrate a Person's name to separate
``firstName`` and ``lastName`` fields. This is also possible by specifying the
``#[AlsoLoad]`` attribute on a method, which will then be invoked immediately
before normal hydration.

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Person
    {
        #[Id]
        public string $id;

        #[Field(type: 'string')]
        public string $firstName;

        #[Field(type: 'string')]
        public string $lastName;

        #[AlsoLoad(['name', 'fullName'])]
        public function populateFirstAndLastName(string $fullName): void
        {
            list($this->firstName, $this->lastName) = explode(' ', $fullName);
        }
    }

The annotation is defined with one or a list of field names. During hydration,
these fields will be checked in order and, for each field present, the annotated
method will be invoked with its value as a single argument. Since the
``firstName`` and ``lastName`` fields are mapped, they would then be updated
when the Person was persisted back to MongoDB.

Unlike lifecycle callbacks, the ``#[AlsoLoad]`` method attribute does not require
the  :ref:`haslifecyclecallbacks` class attribute to be present.

Moving Fields
-------------

Migrating your schema can be a difficult task, but Doctrine provides a few
different methods for dealing with it:

-  **@AlsoLoad** - load values from old fields or transform data through methods
-  **@Field(notSaved=true)** - load values into fields without saving them again
-  **@PostLoad** - execute code after all fields have been loaded
-  **@PrePersist** - execute code before your document gets saved

Imagine you have some address-related fields on a Person document:

.. code-block:: php

    <?php

    #[Document]
    class Person
    {
        #[Id]
        public string $id;

        #[Field(type: 'string')]
        public string $name;

        #[Field(type: 'string')]
        public string $street;

        #[Field(type: 'string')]
        public string $city;
    }

Later on, you may want to migrate this data into an embedded Address document:

.. code-block:: php

    <?php

    #[EmbeddedDocument]
    class Address
    {
        public function __construct(
            #[Field(type: 'string')]
            public string $street,

            #[Field(type: 'string')]
            public string $city,
        ) {
        }
    }

    #[Document]
    #[HasLifecycleCallbacks]
    class Person
    {
        #[Id]
        public string $id;

        #[Field(type: 'string')]
        public string $name;

        #[Field(notSaved: true)]
        public string $street;

        #[Field(notSaved: true)]
        public string $city;

        #[EmbedOne(targetDocument: Address::class)]
        public Address $address;

        #[PostLoad]
        public function postLoad(): void
        {
            if ($this->street !== null || $this->city !== null)
            {
                $this->address = new Address($this->street, $this->city);
            }
        }
    }

Person's ``street`` and ``city`` fields will be hydrated, but not saved. Once
the Person has loaded, the ``postLoad()`` method will be invoked and construct
a new Address object, which is mapped and will be persisted.

Alternatively, you could defer this migration until the Person is saved:

.. code-block:: php

    <?php

    #[Document]
    #[HasLifecycleCallbacks]
    class Person
    {
        // ...

        #[PrePersist]
        public function prePersist(): void
        {
            if ($this->street !== null || $this->city !== null)
            {
                $this->address = new Address($this->street, $this->city);
            }
        }
    }

The :ref:`haslifecyclecallbacks` attribute must be present on the class in
which the method is declared for the lifecycle callback to be registered.

.. _`$rename`: https://docs.mongodb.com/manual/reference/operator/update/rename/
.. _`Objectify`: https://github.com/objectify/objectify
.. _`Objectify schema migration`: https://github.com/objectify/objectify/wiki/SchemaMigration
.. _`$or`: https://docs.mongodb.com/manual/reference/operator/query/or/
