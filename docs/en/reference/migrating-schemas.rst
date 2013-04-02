Migrating Schemas
=================

Even though MongoDB is schemaless, introducing some kind of object
mapper means that the definition of your objects become your
schema. You may have a situation where you rename a property in
your object and you need to also load the values from older
documents where the field is still using the old name. Doctrine
offers a few different methods for dealing with this problem!

.. note::

    Features in this chapter inspired by Objectify
    All of the features documented in this chapter were inspired by
    Objectify which is an object mapper for the AppEngine datastore.
    You can read more about the project on the
    `Objectify Wiki <http://code.google.com/p/objectify-appengine/wiki/Concepts?tm=6>`_.

Renaming a Field
----------------

Lets say you have a document that starts off looking like this:

.. code-block:: php

    <?php

    class Person
    {
        public $id;
        public $name;
    }

Then you want to rename ``name`` to ``fullName`` like this:

.. code-block:: php

    <?php

    class Person
    {
        public $id;
    
        /** @AlsoLoad("name") */
        public $fullName;
    }

When a person is loaded the ``fullName`` field will be populated
with the value of either ``name`` or ``fullName`` since old
documents will have the ``name`` field and new ones will have
``fullName``.

    **CAUTION** The only caveat of this feature is queries do not know
    about the rename. If you query for ``fullName`` only the new
    documents will be returned. You can still query using the ``name``
    field to find the old documents.

Transforming Data
-----------------

Now you may have a situation where you want to store the persons
name in separate first and last name fields. This is also possible.
You can specify the ``@AlsoLoad`` annotation on a method and use it
to do some more complex logic:

.. code-block:: php

    <?php

    class Person
    {
        public $id;
        public $firstName;
        public $lastName;
    
        /** @AlsoLoad({"name", "fullName"}) */
        public function populateFirstAndLastName($fullName)
        {
            $e = explode(' ', $fullName);
            $this->firstName = $e[0];
            $this->lastName = $e[1];
        }
    }

Moving Fields
-------------

Migrating your schema can be a difficult task and Doctrine gives
you a few different methods for dealing with this:

- 
   **@AlsoLoad** - load values from old fields names or transform some
   data using methods.
- 
   **@NotSaved** - load values into fields without saving them again.
-  **@PostLoad** - execute code after all fields have been loaded.
-  **@PrePersist** - execute code before your document gets saved.

Imagine you have some address fields on a Person document:

.. code-block:: php

    <?php

    /** @Document */
    class Person
    {
        /** @Id */
        public $id;

        /** @String */
        public $name;

        /** @String */
        public $street;

        /** @String */
        public $city;
    }

Then later you want to store a persons address in another object as
an embedded document:

.. code-block:: php

    <?php

    /** @EmbeddedDocument */
    class Address
    {
        /** @String */
        public $street;

        /** @String */
        public $city;
    
        public function __construct($street, $city)
        {
            $this->street = $street;
            $this->city = $city;
        }
    }

    /** @Document */
    class Person
    {
        /** @Id */
        public $id;

        /** @String */
        public $name;
    
        /** @NotSaved */
        public $street;
    
        /** @NotSaved */
        public $city;
    
        /** @EmbedOne(targetDocument="Address") */
        public $address;
    
        /** @PostLoad */
        public function postLoad()
        {
            if ($this->street !== null || $this->city !== null)
            {
                $this->address = new Address($this->street, $this->city);
            }
        }
    }

You can also change the data on save if that works better for you:

.. code-block:: php

    <?php

    /** @Document */
    class Person
    {
        // ...
    
        /** @PrePersist */
        public function prePersist()
        {
            if ($this->street !== null || $this->city !== null)
            {
                $this->address = new Address($this->street, $this->city);
            }
        }
    }