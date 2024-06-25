Reference Mapping
=================

This chapter explains how references between documents are mapped with Doctrine.

Collections
-----------

Examples of many-valued references in this manual make use of a ``Collection``
interface and a corresponding ``ArrayCollection`` implementation, which are
defined in the ``Doctrine\Common\Collections`` namespace. These classes have no
dependencies on ODM, and can therefore be used within your domain model and
elsewhere without introducing coupling to the persistence layer.

ODM also provides a ``PersistentCollection`` implementation of ``Collection``,
which incorporates change-tracking functionality; however, this class is
constructed internally during hydration. As a developer, you should develop with
the ``Collection`` interface in mind so that your code can operate with any
implementation.

.. note::

    New in 1.1: you are no longer limited to using ``ArrayCollection`` and can
    freely use your own ``Collection`` implementation. For more details please
    see :doc:`Custom Collections <custom-collections>` chapter.

Why are these classes used over PHP arrays? Native arrays cannot be
transparently extended in PHP, which is necessary for many advanced features
provided by the ODM. Although PHP does provide various interfaces that allow
objects to operate like arrays (e.g. ``Traversable``, ``Countable``,
``ArrayAccess``), and even a concrete implementation in ``ArrayObject``, these
objects cannot always be used everywhere that a native array is accepted.
Doctrine's ``Collection`` interface and ``ArrayCollection`` implementation are
conceptually very similar to ``ArrayObject``, with some slight differences and
improvements.

.. _reference_one:

Reference One
-------------

Reference one document:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class Product
        {
            // ...

            #[ReferenceOne(targetDocument: Shipping::class)]
            private ?Shipping $shipping = null;

            // ...
        }

        #[Document]
        class Shipping
        {
            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\Product">
                <reference-one field="shipping" target-document="Documents\Shipping" />
          </document>
        </doctrine-mongo-mapping>

.. _reference_many:

Reference Many
--------------

Reference many documents:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class User
        {
            // ...

            /** @var Collection<Account> */
            #[ReferenceMany(targetDocument: Account::class)]
            private Collection $accounts;

            public function __construct()
            {
                $this->accounts = new ArrayCollection();
            }

            // ...
        }

        #[Document]
        class Account
        {
            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\Product">
                <reference-many field="accounts" target-document="Documents\Account" />
          </document>
        </doctrine-mongo-mapping>

.. _reference_mixing_document_types:

Mixing Document Types
---------------------

If you want to store different types of documents in references, you can simply
omit the ``targetDocument`` option:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class User
        {
            // ..

            #[ReferenceMany]
            private Collection $favorites;

            // ...
        }

    .. code-block:: xml

        <field field-name="favorites" />

Now the ``$favorites`` property can store a reference to any type of document!
The class name will be automatically stored in a field named
``_doctrine_class_name`` within the `DBRef`_ object.

.. note::

    The MongoDB shell tends to ignore fields other than ``$id`` and ``$ref``
    when displaying `DBRef`_ objects. You can verify the presence of any ``$db``
    and discriminator fields by querying and examining the document with a
    driver. See `SERVER-10777 <https://jira.mongodb.org/browse/SERVER-10777>`_
    for additional discussion on this issue.

The name of the field within the DBRef object can be customized via the
``discriminatorField`` option:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class User
        {
            // ..

            #[ReferenceMany(discriminatorField: 'type')]
            private Collection $favorites;

            // ...
        }

    .. code-block:: xml

        <reference-many field-name="favorites">
            <discriminator-field name="type" />
        </reference-many>

You can also specify a discriminator map to avoid storing the |FQCN|
in each `DBRef`_ object:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class User
        {
            // ..

            /** @var Collection<Album|Song> */
            #[ReferenceMany(
                discriminatorMap: [
                    'album' => Album::class,
                    'song' => Song::class,
                ]
            )]
            private Collection $favorites;

            // ...
        }

    .. code-block:: xml

        <reference-many field-name="favorites">
            <discriminator-map>
                <discriminator-mapping value="album" class="Documents\Album" />
                <discriminator-mapping value="song" class="Documents\Song" />
            </discriminator-map>
        </reference-many>

If you have references without a discriminator value that should be considered
a certain class, you can optionally specify a default discriminator value:

.. configuration-block::

    .. code-block:: php

        <?php

        #[Document]
        class User
        {
            // ..

            /** @var Collection<Album|Song> */
            #[ReferenceMany(
                discriminatorMap: [
                    'album' => Album::class,
                    'song' => Song::class,
                ],
                defaultDiscriminatorValue: 'album',
            )]
            private Collection $favorites;

            // ...
        }

    .. code-block:: xml

        <reference-many field-name="favorites">
            <discriminator-map>
                <discriminator-mapping value="album" class="Documents\Album" />
                <discriminator-mapping value="song" class="Documents\Song" />
            </discriminator-map>
            <default-discriminator-value value="album" />
        </reference-many>

.. _storing_references:

Storing References
------------------

By default all references are stored as a `DBRef`_ object with the traditional
``$ref``, ``$id``, and (optionally) ``$db`` fields (in that order). For references to
documents of a single collection, storing the collection (and database) names for
each reference may be redundant. You can use ID references to store the
referenced document's identifier (e.g. ``MongoDB\BSON\ObjectId``) instead of a
`DBRef`_.

Example:

.. configuration-block::

    .. code-block:: php

        <?php

        class User
        {
            #[ReferenceOne(targetDocument: Profile::class, storeAs: 'id')]
            private Profile $profile;
        }

    .. code-block:: xml

        <reference-one target-document="Documents\Profile", store-as="id" />

Now, the ``profile`` field will only store the ``MongoDB\BSON\ObjectId`` of the
referenced Profile document.

ID references reduce the amount of storage used, both for the document
itself and any indexes on the reference field; however, ID references cannot
be used with discriminators, since there is no `DBRef`_ object in which to store
a discriminator value.

In addition to saving references as `DBRef`_ with ``$ref``, ``$id``, and ``$db``
fields and as ``MongoDB\BSON\ObjectId``, it is possible to save references as
`DBRef`_ without the ``$db`` field. This solves problems when the database name
changes (and also reduces the amount of storage used).

The ``storeAs`` option has the following possible values:

- **dbRefWithDb**: Uses a `DBRef`_ with ``$ref``, ``$id``, and ``$db`` fields
- **dbRef**: Uses a `DBRef`_ with ``$ref`` and ``$id`` (this is the default)
- **ref**: Uses a custom embedded object with an ``id`` field
- **id**: Uses the identifier of the referenced object

.. note::

    Up until 2.0 ``storeAs=dbRefWithDb`` was the default setting. If you have data in
    the old format, you should add ``storeAs=dbRefWithDb`` to all your references, or
    update the database references (deleting the ``$db`` field) as ``storeAs=dbRef``
    is the new default setting.

Cascading Operations
--------------------

By default, Doctrine will not cascade any ``UnitOfWork`` operations to
referenced documents. You must explicitly enable this functionality:

.. configuration-block::

    .. code-block:: php

        <?php

        class User
        {
            #[ReferenceOne(targetDocument: Profile::class, cascade: ['persist'])]
            private Profile $profile;
        }

    .. code-block:: xml

        <reference-one target-document="Documents\Profile">
            <cascade>
                <persist/>
            </cascade>
        </reference-one>

The valid values are:

-  **all** - cascade all operations by default.
-  **detach** - cascade detach operation to referenced documents.
-  **merge** - cascade merge operation to referenced documents.
-  **refresh** - cascade refresh operation to referenced documents.
-  **remove** - cascade remove operation to referenced documents.
-  **persist** - cascade persist operation to referenced documents.

Orphan Removal
--------------

There is another concept of cascading that is relevant only when removing documents
from collections. If a Document of type ``A`` contains references to privately
owned Documents ``B`` then if the reference from ``A`` to ``B`` is removed the
document ``B`` should also be removed, because it is not used anymore.

OrphanRemoval works with both reference one and many mapped fields.

.. note::

    When using the ``orphanRemoval=true`` option Doctrine makes the assumption
    that the documents are privately owned and will **NOT** be reused by other documents.
    If you neglect this assumption your documents will get deleted by Doctrine even if
    you assigned the orphaned documents to another one.

As a better example consider an Addressbook application where you have Contacts, Addresses
and StandingData:

.. code-block:: php

    <?php

    namespace Addressbook;

    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;

    #[Document]
    class Contact
    {
        #[Id]
        private string $id;

        #[ReferenceOne(targetDocument: StandingData::class, orphanRemoval: true)]
        private ?StandingData $standingData;

        #[ReferenceMany(targetDocument: Address::class, mappedBy: 'contact', orphanRemoval: true)]
        private Collection $addresses;

        public function __construct()
        {
            $this->addresses = new ArrayCollection();
        }

        public function newStandingData(StandingData $sd): void
        {
            $this->standingData = $sd;
        }

        public function removeAddress($pos): void
        {
            unset($this->addresses[$pos]);
        }
    }

Now two examples of what happens when you remove the references:

.. code-block:: php

    <?php

    $contact = $dm->find(Addressbook\Contact::class, $contactId);
    $contact->newStandingData(new StandingData('Firstname', 'Lastname', 'Street'));
    $contact->removeAddress(1);

    $dm->flush();

In this case you have not only changed the ``Contact`` document itself but
you have also removed the references for standing data and as well as one
address reference. When flush is called not only are the references removed
but both the old standing data and the one address documents are also deleted
from the database.

.. _`DBRef`: https://docs.mongodb.com/manual/reference/database-references/#dbrefs
.. |FQCN| raw:: html
  <abbr title="Fully-Qualified Class Name">FQCN</abbr>

.. _store_empty_array:

Storing Empty Arrays
---------------------

By default, when a collection property is empty, Doctrine does not store any data for it in the database.
However, in some cases, you may want to explicitly store an empty array for such properties.
You can achieve this behavior by using the `storeEmptyArray` option.

.. configuration-block::

    .. code-block:: php
        <?php

        #[Document]
        class User
        {
            // ...

            /** @var Collection<Account> */
            #[ReferenceMany(targetDocument: Account::class, storeEmptyArray: true)]
            private Collection $accounts;

            // ...
        }
    .. code-block:: xml
        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <reference-many field="accounts" target-document="Documents\Account" store-empty-array="true" />
          </document>
        </doctrine-mongo-mapping>
Now, when the `$accounts` collection is empty, an empty array will be stored in the database for the `User` document,
even if there are no actual referenced documents.
