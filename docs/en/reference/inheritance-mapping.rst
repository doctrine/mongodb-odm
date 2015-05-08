.. _inheritance_mapping:

Inheritance Mapping
===================

Doctrine currently offers two supported methods of inheritance:
:ref:`single collection <single_collection_inheritance>` and
:ref:`collection per class <collection_per_class_inheritance>` inheritance.

Mapped Superclasses
-------------------

A mapped superclass is an abstract or concrete class that provides mapping
information for its subclasses, but is not itself a document. Typically, the
purpose of such a mapped superclass is to define state and mapping information
that is common to multiple document classes.

Just like non-mapped classes, mapped superclasses may appear in the middle of
an otherwise mapped inheritance hierarchy (through
:ref:`single collection <single_collection_inheritance>` or
:ref:`collection per class <collection_per_class_inheritance>`) inheritance.

.. note::

    A mapped superclass cannot be a document and is not queryable.

Example:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @MappedSuperclass */
        abstract class BaseDocument
        {
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <mapped-superclass name="Documents\BaseDocument">
          </mapped-superclass>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        Documents\BaseDocument:
            type: mappedSuperclass

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
    
        /**
         * @Document
         * @InheritanceType("SINGLE_COLLECTION")
         * @DiscriminatorField("type")
         * @DiscriminatorMap({"person"="Person", "employee"="Employee"})
         */
        class Person
        {
            // ...
        }
    
        /**
         * @Document
         */
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

    .. code-block:: yaml

        Documents\Person:
          type: document
          inheritanceType: SINGLE_COLLECTION
          discriminatorField: type
          discriminatorMap:
            person: Person
            employee: Employee

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
    
    $employee = $dm->find('Person', $employee->getId()); // instanceof Employee

Even though we queried for a Person, Doctrine will know to return an Employee
instance because of the discriminator map!

If your document structure has changed and you've added discriminators after
already having a bunch of documents, you can specify a default value for the
discriminator field:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /**
         * @Document
         * @InheritanceType("SINGLE_COLLECTION")
         * @DiscriminatorField("type")
         * @DiscriminatorMap({"person"="Person", "employee"="Employee"})
         * @DefaultDiscriminatorValue("person")
         */
        class Person
        {
            // ...
        }

        /**
         * @Document
         */
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

    .. code-block:: yaml

        Documents\Person:
          type: document
          inheritanceType: SINGLE_COLLECTION
          discriminatorField: type
          defaultDiscriminatorValue: person
          discriminatorMap:
            person: Person
            employee: Employee

.. _collection_per_class_inheritance:

Collection Per Class Inheritance
--------------------------------

With collection per class inheritance, each document is stored in its own
collection and contains all inherited fields:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;
    
        /**
         * @Document
         * @InheritanceType("COLLECTION_PER_CLASS")
         */
        class Person
        {
            // ...
        }
    
        /**
         * @Document
         */
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
          <document name="Documents\Person" inheritance-type="COLLECTION_PER_CLASS">
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

    .. code-block:: yaml

        Documents\Person:
          type: document
          inheritanceType: COLLECTION_PER_CLASS

A discriminator is not needed with this type of inheritance since the data is
separated in different collections.
