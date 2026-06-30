.. _inheritance_mapping:

Inheritance Mapping
===================

Doctrine supports two approaches to inheritance:

- :ref:`Single collection <single_collection_inheritance>` inheritance, where
  all classes in the hierarchy are stored in the same collection and a
  discriminator field identifies each document type.
- :ref:`Sharing common fields <sharing_common_fields>` across document classes
  stored in different collections, using PHP inheritance or traits.

.. _single_collection_inheritance:

Single Collection Inheritance
-----------------------------

In single collection inheritance, each document is stored in a single collection
and a discriminator field is used to distinguish one document type from another.

Simple example:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        #[Document]
        #[InheritanceType('SINGLE_COLLECTION')]
        #[DiscriminatorField('type')]
        #[DiscriminatorMap(['person' => Person::class, 'employee' => Employee::class])]
        class Person
        {
            // ...
        }

        #[Document]
        class Employee extends Person
        {
            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\Person" inheritance-type="SINGLE_COLLECTION">
            <discriminator-field name="type" />
            <discriminator-map>
                <discriminator-mapping value="person" class="Person" />
                <discriminator-mapping value="employee" class="Employee" />
            </discriminator-map>
          </document>
        </doctrine-mongo-mapping>

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\Employee">
          </document>
        </doctrine-mongo-mapping>

The discriminator value allows Doctrine to infer the class name to instantiate
when hydrating a document. If a discriminator map is used, the discriminator
value will be used to look up the class name in the map.

Now, if we query for a Person and its discriminator value is ``employee``, we
would get an Employee instance back:

.. code-block:: php

    <?php

    $employee = new Employee();
    // ...
    $dm->persist($employee);
    $dm->flush();

    $employee = $dm->find(Person::class, $employee->getId()); // instanceof Employee

Even though we queried for a Person, Doctrine will know to return an Employee
instance because of the discriminator map!

If your document structure has changed and you've added discriminators after
already having a bunch of documents, you can specify a default value for the
discriminator field:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        #[Document]
        #[InheritanceType('SINGLE_COLLECTION')]
        #[DiscriminatorField('type')]
        #[DiscriminatorMap(['person' => Person::class, 'employee' => Employee::class])]
        #[DefaultDiscriminatorValue('person')]
        class Person
        {
            // ...
        }

        #[Document]
        class Employee extends Person
        {
            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\Person" inheritance-type="SINGLE_COLLECTION">
            <discriminator-field name="type" />
            <discriminator-map>
                <discriminator-mapping value="person" class="Person" />
                <discriminator-mapping value="employee" class="Employee" />
            </discriminator-map>
            <default-discriminator-value value="person" />
          </document>
        </doctrine-mongo-mapping>

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\Employee">
          </document>
        </doctrine-mongo-mapping>

.. _sharing_common_fields:

Sharing Common Fields
---------------------

To share common fields across multiple document classes stored in different
collections, use a PHP abstract parent class or a trait. ODM reads field
mappings from all properties of a class through reflection, whether they are
declared in the class itself, a parent class, or a trait.

Each class is stored in its own collection. Unlike :ref:`single collection
inheritance <single_collection_inheritance>`, there is no discriminator field
and documents are not polymorphically queryable across classes.

Abstract parent class
~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    namespace Documents;

    abstract class BaseDocument
    {
        #[Field]
        private string $createdBy;
    }

    #[Document(collection: 'articles')]
    class Article extends BaseDocument
    {
        // inherits $createdBy
    }

    #[Document(collection: 'comments')]
    class Comment extends BaseDocument
    {
        // inherits $createdBy
    }

PHP trait
~~~~~~~~~

.. code-block:: php

    <?php

    namespace Documents;

    trait Timestamps
    {
        #[Field]
        private DateTimeImmutable $createdAt;

        #[Field]
        private DateTimeImmutable $updatedAt;
    }

    #[Document(collection: 'articles')]
    class Article
    {
        use Timestamps;
    }

    #[Document(collection: 'comments')]
    class Comment
    {
        use Timestamps;
    }

Mapped Superclass
~~~~~~~~~~~~~~~~~

When using XML mapping, each class requires its own mapping file. To share
fields from a parent class, declare it as a ``mapped-superclass`` so the driver
knows to load its mapping file:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        #[MappedSuperclass]
        abstract class BaseDocument
        {
            #[Field]
            private string $createdBy;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <mapped-superclass name="Documents\BaseDocument">
            <field fieldName="createdBy" type="string" />
          </mapped-superclass>
        </doctrine-mongo-mapping>

.. note::

    A mapped superclass cannot be a document and is not queryable.
