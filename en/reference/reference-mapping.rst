This chapter explains how references between documents are mapped
with Doctrine.

Collections
-----------

In all the examples of many-valued references in this manual we
will make use of a ``Collection`` interface and a corresponding
default implementation ``ArrayCollection`` that are defined in the
``Doctrine\Common\Collections`` namespace. Why do we need that?
Doesn't that couple my domain model to Doctrine? Unfortunately, PHP
arrays, while being great for many things, do not make up for good
collections of business objects, especially not in the context of
an ODM. The reason is that plain PHP arrays can not be
transparently extended / instrumented in PHP code, which is
necessary for a lot of advanced ODM features. The classes /
interfaces that come closest to an OO collection are ArrayAccess
and ArrayObject but until instances of these types can be used in
all places where a plain array can be used (something that may
happen in PHP6) their usability is fairly limited. You "can"
type-hint on ``ArrayAccess`` instead of ``Collection``, since the
Collection interface extends ``ArrayAccess``, but this will
severely limit you in the way you can work with the collection,
because the ``ArrayAccess`` API is (intentionally) very primitive
and more importantly because you can not pass this collection to
all the useful PHP array functions, which makes it very hard to
work with.

    **CAUTION** The Collection interface and ArrayCollection class,
    like everything else in the Doctrine namespace, are neither part of
    the ODM, it is a plain PHP class that has no outside dependencies
    apart from dependencies on PHP itself (and the SPL). Therefore
    using this class in your domain classes and elsewhere does not
    introduce a coupling to the persistence layer. The Collection
    class, like everything else in the Common namespace, is not part of
    the persistence layer. You could even copy that class over to your
    project if you want to remove Doctrine from your project and all
    your domain classes will work the same as before.


Reference One
-------------

Reference one document:

::

    <?php
    /** @Document */
    class Product
    {
        // ...
    
        /**
         * @ReferenceOne(targetDocument="Shipping")
         */
        private $shipping;
    
        // ...
    }
    
    /** @Document */
    class Shipping
    {
        // ...
    }

Reference Many
--------------

Reference many documents:

::

    <?php
    /** @Document */
    class User
    {
        // ...
    
        /**
         * @ReferenceMany(targetDocument="Phonenumber")
         */
        private $phonenumbers = array();
    
        // ...
    }
    
    /** @Document */
    class Phonenumber
    {
        // ...
    }

Mixing Document Types
---------------------

If you want to store different types of documents in references you
can simply omit the ``targetDocument`` option:

::

    <?php
    /** @Document */
    class User
    {
        // ..
    
        /** @ReferenceMany */
        private $favorites = array();
    
        // ...
    }

Now the ``$favorites`` property can store a reference to any type
of document! The class name will be automatically added for you in
a field named ``_doctrine_class_name``.

You can also specify a discriminator map to avoid storing the fully
qualified class name with each reference:

::

    <?php
    /** @Document */
    class User
    {
        // ..
    
        /**
         * @ReferenceMany(
         *   discriminatorMap={
         *     "album"="Album",
         *     "song"="Song"
         *   }
         * )
         */
        private $favorites = array();
    
        // ...
    }

You can have different classes that can be referenced:

::

    <?php
    /** @Document */
    class Album
    {
        // ...
    }
    
    /** @Document */
    class Song
    {
        // ...
    }

If you want to store the discriminator value in a field other than
``_doctrine_class_name`` you can use the ``discriminatorField``
option:

::

    <?php
    /** @Document */
    class User
    {
        // ..
    
        /**
         * @ReferenceMany(discriminatorField="type")
         */
        private $favorites = array();
    
        // ...
    }

Cascading Operations
--------------------

By default Doctrine will not cascade any ``UnitOfWork`` operations
to referenced documents so if wish to have this functionality you
must explicitly enable it:

::

    <?php
    /**
     * @ReferenceMany(discriminatorField="type", cascade={"all"})
     */
    private $favorites = array();

The valid values are:


-  **all** - cascade on all operations by default.
-  **detach** - cascade detach operation to referenced documents.
-  **merge** - cascade merge operation to referenced documents.
-  **refresh** - cascade refresh operation to referenced documents.
-  **remove** - cascade remove operation to referenced documents.
- 
   **persist** - cascade persist operation to referenced documents.


