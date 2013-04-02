Inheritance Mapping
===================

Doctrine currently offers two supported methods of inheritance
which are Single Collection Inheritance and Collection Per Class
Inheritance.

Mapped Superclasses
-------------------

An mapped superclass is an abstract or concrete class that provides
persistent document state and mapping information for its
subclasses, but which is not itself a document. Typically, the
purpose of such a mapped superclass is to define state and mapping
information that is common to multiple document classes.

Mapped superclasses, just as regular, non-mapped classes, can
appear in the middle of an otherwise mapped inheritance hierarchy
(through Single Collection Inheritance or Collection Per Class
Inheritance).

.. note::

    A mapped superclass cannot be a document and is not query able.

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

Single Collection Inheritance
-----------------------------

In Single Collection Inheritance each document is stored in a
single collection where a discriminator field is used to
distinguish one document type from another.

Simple example:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;
    
        /**
         * @Document
         * @InheritanceType("SINGLE_COLLECTION")
         * @DiscriminatorField(fieldName="type")
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
            <discriminator-field name="type=" fieldName="type" />
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
          discriminatorMap:
            person: Person
            employee: Employee

The discriminator field value allows Doctrine to know what type of
class to return by looking it up in the discriminator map. Now if
we ask for a certain Person and it has a discriminator field value
of employee, we would get an Employee instance back:

.. code-block:: php

    <?php

    $employee = new Employee();
    // ...
    $dm->persist($employee);
    $dm->flush();
    
    $employee = $dm->find('Person', $employee->getId()); // instanceof Employee

Even though we queried Person, Doctrine will know to return an
Employee instance because of the discriminator map!

Collection Per Class Inheritance
--------------------------------

With Collection Per Class Inheritance each document is stored in
its own collection and contains all inherited fields:

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

In this type of inheritance a discriminator is not needed since the
data is separated in different collections!