Architecture
============

This chapter gives an overview of the overall architecture,
terminology and constraints of Doctrine. It is recommended to
read this chapter carefully.

Documents
---------

A document is a lightweight, persistent domain object. A document can
be any regular PHP class observing the following restrictions:

-  A document class must not be final or contain final methods.
-  All persistent properties/field of any document class should
   always be private or protected, otherwise lazy-loading might not
   work as expected.
-  Any two document classes in a class hierarchy that inherit
   directly or indirectly from one another must not have a mapped
   property with the same name. That is, if B inherits from A then B
   must not have a mapped field with the same name as an already
   mapped field that is inherited from A.

Documents support inheritance, polymorphic associations, and
polymorphic queries. Both abstract and concrete classes can be
documents. Documents may extend non-document classes as well as document
classes, and non-document classes may extend document classes.

.. tip::

    The constructor of a document is only ever invoked when
    *you* construct a new instance with the *new* keyword. Doctrine
    never calls document constructors, thus you are free to use them as
    you wish and even have it require arguments of any type.

Document states
~~~~~~~~~~~~~~~

A document instance can be characterized as being NEW, MANAGED, DETACHED or REMOVED.

-  A NEW document instance has no persistent identity, and is not yet
   associated with a DocumentManager and a UnitOfWork (i.e. those just
   created with the "new" operator).
-  A MANAGED document instance is an instance with a persistent
   identity that is associated with a DocumentManager and whose
   persistence is thus managed.
-  A DETACHED document instance is an instance with a persistent
   identity that is not (or no longer) associated with a
   DocumentManager and a UnitOfWork.
-  A REMOVED document instance is an instance with a persistent
   identity, associated with a DocumentManager, that will be removed
   from the database upon UnitOfWork commit.

Persistent fields
~~~~~~~~~~~~~~~~~

The persistent state of a document is represented by instance
variables. An instance variable must be directly accessed only from
within the methods of the document by the document instance itself.
Instance variables must not be accessed by clients of the document.
The state of the document is available to clients only through the
document's methods, i.e. accessor methods (getter/setter methods) or
other business methods.

Collection-valued persistent fields and properties must be defined
in terms of the ``Doctrine\Common\Collections\Collection``
interface. The collection implementation type may be used by the
application to initialize fields or properties before the document is
made persistent. Once the document becomes managed (or detached),
subsequent access must be through the interface type.

Serializing documents
~~~~~~~~~~~~~~~~~~~~~

Calling `serialize` for serializing documents does not trigger initialization
of lazy objects in relations. Instead, only the identifier fields are serialized.
When unserializing, the objects that were not initialized before serialization
will not contain any data except for the identifier fields. They cannot be used.

The DocumentManager
-------------------

The ``DocumentManager`` class is a central access point to the ODM
functionality provided by Doctrine. The ``DocumentManager`` API is
used to manage the persistence of your objects and to query for
persistent objects.

Transactional write-behind
~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``DocumentManager`` and the underlying ``UnitOfWork`` employ a
strategy called "transactional write-behind" that delays the
execution of query statements in order to execute them in the most
efficient way and to execute them at the end of a transaction so
that all write locks are quickly released. You should see Doctrine
as a tool to synchronize your in-memory objects with the database
in well defined units of work. Work with your objects and modify
them as usual and when you're done call ``DocumentManager#flush()``
to make your changes persistent.

The Unit of Work
~~~~~~~~~~~~~~~~

Internally an ``DocumentManager`` uses a ``UnitOfWork``, which is a
typical implementation of the
`Unit of Work pattern <http://martinfowler.com/eaaCatalog/unitOfWork.html>`_,
to keep track of all the things that need to be done the next time
``flush`` is invoked. You usually do not directly interact with a
``UnitOfWork`` but with the ``DocumentManager`` instead.
