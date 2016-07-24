Best Practices
==============

Here are some best practices you can follow when working with the Doctrine MongoDB ODM.

Constrain relationships as much as possible
-------------------------------------------

It is important to constrain relationships as much as possible. This means:

-  Impose a traversal direction (avoid bidirectional associations if possible)
-  Eliminate nonessential associations

This has several benefits:

-  Reduced coupling in your domain model
-  Simpler code in your domain model (no need to maintain bidirectionality properly)
-  Less work for Doctrine

Use events judiciously
----------------------

The event system of Doctrine is great and fast. Even though making
heavy use of events, especially lifecycle events, can have a
negative impact on the performance of your application. Thus you
should use events judiciously.

Use cascades judiciously
------------------------

Automatic cascades of the persist/remove/merge/etc. operations are
very handy but should be used wisely. Do NOT simply add all
cascades to all associations. Think about which cascades actually
do make sense for you for a particular association, given the
scenarios it is most likely used in.

Don't use special characters
----------------------------

Avoid using any non-ASCII characters in class, field, table or
column names. Doctrine itself is not unicode-safe in many places
and will not be until PHP itself is fully unicode-aware.

Initialize collections in the constructor
-----------------------------------------

It is recommended best practice to initialize any business
collections in documents in the constructor.

Example:

.. code-block:: php

    <?php

    namespace MyProject\Model;

    use Doctrine\Common\Collections\ArrayCollection;
    
    class User
    {
        private $addresses;
        private $articles;
    
        public function __construct()
        {
            $this->addresses = new ArrayCollection;
            $this->articles = new ArrayCollection;
        }
    }
