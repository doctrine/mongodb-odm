Complex References
==================

Sometimes you may want to access related documents using custom criteria or from
the inverse side of a relationship.

You can create an `immutable`_ reference to one or many documents and specify
how that reference is to be loaded. The reference is immutable in that it is
defined only in the mapping, unlike a typical reference where a `MongoDBRef`_ or
identifier (see :ref:`storing_references`) is stored on the document itself.

The following options may be used for :ref:`one <reference_one>` and
:ref:`many <reference_many>` reference mappings:

 - ``criteria`` - Query criteria to apply to the cursor.
 - ``repositoryMethod`` - The repository method used to create the cursor.
 - ``sort`` - Sort criteria for the cursor.
 - ``skip`` - Skip offset to apply to the cursor.
 - ``limit`` - Limit to apply to the cursor.

Basic Example
-------------

In the following example, ``$comments`` will refer to all Comments for the
BlogPost and ``$last5Comments`` will refer to only the last five Comments. The
``mappedBy`` field is used to determine which Comment field should be used for
querying by the BlogPost's ID.

.. code-block:: php

    <?php

    /** @Document */
    class BlogPost
    {
        // ...

        /** @ReferenceMany(targetDocument="Comment", mappedBy="blogPost") */
        private $comments;

        /**
         * @ReferenceMany(
         *      targetDocument="Comment",
         *      mappedBy="blogPost",
         *      sort={"date"="desc"},
         *      limit=5
         * )
         */
        private $last5Comments;
    }

    /** @Document */
    class Comment
    {
        // ...

        /** @ReferenceOne(targetDocument="BlogPost", inversedBy="comments") */
        private $blogPost;
    }

You can also use ``mappedBy`` for referencing a single document, as in the
following example:

.. code-block:: php

    <?php

    /**
     * @ReferenceOne(
     *      targetDocument="Comment",
     *      mappedBy="blogPost",
     *      sort={"date"="desc"}
     * )
     */
    private $lastComment;


``criteria`` Example
--------------------

Use ``criteria`` to further match referenced documents. In the following
example, ``$commentsByAdmin`` will refer only comments created by
administrators:

.. code-block:: php

    <?php

    /**
     * @ReferenceMany(
     *      targetDocument="Comment",
     *      mappedBy="blogPost",
     *      criteria={"isByAdmin" : true}
     * )
     */
    private $commentsByAdmin;

``repositoryMethod`` Example
----------------------------

Alternatively, you can use ``repositoryMethod`` to specify a custom method to
call on the Comment repository class to populate the reference.

.. code-block:: php

    <?php

    /**
     * @ReferenceMany(
     *      targetDocument="Comment",
     *      mappedBy="blogPost",
     *      repositoryMethod="findSomeComments"
     * )
     */
    private $someComments;

The ``Comment`` class will need to have a custom repository class configured:

.. code-block:: php

    <?php

    /** @Document(repositoryClass="CommentRepository") */
    class Comment
    {
        // ...
    }

Lastly, the ``CommentRepository`` class will need a ``findSomeComments()``
method which shall return ``Doctrine\MongoDB\CursorInterface``. When this method
is called to populate the reference, Doctrine will provide the Blogpost instance
(i.e. owning document) as the first argument:

.. code-block:: php

    <?php

    class CommentRepository extends \Doctrine\ODM\MongoDB\DocumentRepository
    {
        /**
         * @return \Doctrine\ODM\MongoDB\Cursor
         */
        public function findSomeComments(BlogPost $blogPost)
        {
            return $this->createQueryBuilder()
                ->field('blogPost')->references($blogPost);
                ->getQuery()->execute();
        }
    }

.. _MongoDBRef: http://php.net/manual/en/class.mongodbref.php
.. _immutable: http://en.wikipedia.org/wiki/Immutable
