Implementing Wakeup or Clone
============================

.. sectionauthor:: Roman Borschel (roman@code-factory.org)

As explained in the
:doc:`restrictions for document classes in the manual <../reference/architecture>`.
it is usually not allowed for a document to implement ``__wakeup``
or ``__clone``, because Doctrine makes special use of them.
However, it is quite easy to make use of these methods in a safe
way by guarding the custom wakeup or clone code with a document
identity check, as demonstrated in the following sections.

Safely implementing \_\_wakeup
------------------------------

To safely implement ``__wakeup``, simply enclose your
implementation code in an identity check as follows:

.. code-block:: php

    <?php

    class MyDocument
    {
        private $id; // This is the identifier of the document.
        //...
    
        public function __wakeup()
        {
            // If the document has an identity, proceed as normal.
            if ($this->id) {
                // ... Your code here as normal ...
            }
            // otherwise do nothing, do NOT throw an exception!
        }
    
        //...
    }

Safely implementing \_\_clone
-----------------------------

Safely implementing ``__clone`` is pretty much the same:

.. code-block:: php

    <?php
    class MyDocument
    {
        private $id; // This is the identifier of the document.
        //...
    
        public function __clone()
        {
            // If the document has an identity, proceed as normal.
            if ($this->id) {
                // ... Your code here as normal ...
            }
            // otherwise do nothing, do NOT throw an exception!
        }
    
        //...
    }

Summary
-------

As you have seen, it is quite easy to safely make use of
``__wakeup`` and ``__clone`` in your documents without adding any
really Doctrine-specific or Doctrine-dependant code.

These implementations are possible and safe because when Doctrine
invokes these methods, the documents never have an identity (yet).
Furthermore, it is possibly a good idea to check for the identity
in your code anyway, since it's rarely the case that you want to
unserialize or clone a document with no identity.