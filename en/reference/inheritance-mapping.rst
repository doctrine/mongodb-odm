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

    **NOTE**

    A mapped superclass cannot be a document, it is not queryable and
    persistent relationships defined by a mapped superclass must be
    unidirectional. For further support of inheritance, the single or
    joined table inheritance features have to be used.


Example:

.. code-block:: php

    <?php
    /** @MappedSuperclass */
    class MappedSuperclassBase
    {
        /** @Int */
        private $mapped1;
        /** @String */
        private $mapped2;
        /**
         * @ReferenceOne(targetDocument="MappedSuperclassRelated1")
         */
        private $mappedRelated1;
    
        // ... more fields and methods
    }
    
    /** @Document */
    class DocumentSubClass extends MappedSuperclassBase
    {
        /** @Id */
        private $id;
        /** @String */
        private $name;
    
        // ... more fields and methods
    }

Single Collection Inheritance
-----------------------------

In Single Collection Inheritance each document is stored in a
single collection where a discriminator field is used to
distinguish one document type from another.

Simple example:

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

The discriminator field value allows Doctrine to know what type of
class to return by looking it up in the discriminator map. Now if
we ask for a certain Person and it has a discriminator field value
of employee, we would get an Employee instance back:

.. code-block:: php

    <?php
    $employee = new Empoyee();
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

In this type of inheritance a discriminator is not needed since the
data is separated in different collections!


