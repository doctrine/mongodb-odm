Complex References
==================

Sometimes you may want to access references on the inverse side of a relationship where you
maintain the actual `MongoDbRef`_ to one or many other documents.

You can create an `immutable`_ reference to one or many documents and specify how that reference
is to be loaded. The available methods for obtaining a cursor to retrieve the references are:

 - ``criteria`` - The criteria used to get a cursor.
 - ``repositoryMethod`` - The repository method used to get a cursor. 
 - ``sort`` - What to sort the cursor by.
 - ``skip`` - The number of documents to skip in the cursor.
 - ``limit`` - The number of documents to limit the cursor to.

Here is an example. You could setup a reference to the last 5 comments for blog posts:

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

You can specify a ``mappedBy`` reference for one or many so if you wanted to you could have even a
``$lastComment`` reference on the ``BlogPost``:

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

Use an array of criteria to limit a references documents. Here we have a reference in ``$commentsByAdmin``
to the comments that are by administrators:

.. code-block:: php

    <?php
    
    /**
     * @ReferenceOne(
     *      targetDocument="Comment",
     *      mappedBy="blogPost",
     *      criteria={"isByAdmin" : true}
     * )
     */
    private $commentsByAdmin;

Or you can use the ``repositoryMethod`` to specify a custom method to call on the mapped repository
class to get the reference:

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

Now on the ``Comment`` class you would need to have a custom repository class configured:

.. code-block:: php

    <?php

    /** @Document(repositoryClass="CommentRepository") */
    class Comment
    {
        // ...
    }

And in the ``CommentRepository`` we can define the ``findSomeComments()`` method:

.. code-block:: php

    <?php

    class CommentRepository extends \Doctrine\ODM\MongoDB\DocumentRepository
    {
        public function findSomeComments()
        {
            return $this->findBy(array(/** ... */));
        }
    }

.. _MongoDbRef: http://php.net/MongoDbRef
.. _immutable: http://en.wikipedia.org/wiki/Immutable