Trees
=====

MongoDB lends itself quite well to storing hierarchical data. This
chapter will demonstrate some examples!

Full Tree in Single Document
----------------------------

.. code-block:: php

    <?php
    /** @Document */
    class BlogPost
    {
        /** @Id */
        private $id;
    
        /** @String */
        private $title;
    
        /** @String */
        private $body;
    
        /** @EmbedMany(targetDocument="Comment") */
        private $comments = array();
    
        // ...
    }
    
    /** @EmbeddedDocument */
    class Comment
    {
        /** @String */
        private $by;
    
        /** @String */
        private $text;
    
        /** @EmbedMany(targetDocument="Comment") */
        private $replies = array();
    
        // ...
    }

Retrieve a blog post and only select the first 10 comments:

.. code-block:: php

    <?php
    $post = $dm->createQuery('BlogPost')
        ->selectSlice('replies', 0, 10);
    $replies = $post->getReplies();

You can read more about this pattern on the
`MongoDB <http://www.mongodb.org/display/DOCS/Trees+in+MongoDB#TreesinMongoDB-FullTreeinSingleDocument>`_
website!

Parent Reference
----------------

.. code-block:: php

    <?php
    /** @Document */
    class Category
    {
        /** @Id */
        private $id;
    
        /** @String */
        private $name;
    
        /**
         * @ReferenceOne(targetDocument="Category")
         * @Index
         */
        private $parent;
    
        // ...
    }

Query for children by a specific parent id:

.. code-block:: php

    <?php
    $children = $dm->createQuery('Category')
        ->field('parent.$id')->equals(new \MongoId('theid'))
        ->execute();

You can read more about this pattern on the
`MongoDB <http://www.mongodb.org/display/DOCS/Trees+in+MongoDB#TreesinMongoDB-ParentLinks>`_
website!

Child Reference
---------------

.. code-block:: php

    <?php
    /** @Document */
    class Category
    {
        /** @Id */
        private $id;
    
        /** @String */
        private $name;
    
        /**
         * @ReferenceMany(targetDocument="Category")
         * @Index
         */
        private $children = array();
    
        // ...
    }

Query for immediate children of a category:

.. code-block:: php

    <?php
    $category = $dm->createQuery('Category')
        ->field('id')->equals(new \MongoId('theid'))
        ->execute();
    $children = $category->getChildren();

Query for immediate parent of a category:

.. code-block:: php

    <?php
    $parent = $dm->createQuery('Category')
        ->field('children.$id')->equals(new \MongoId('theid'))
        ->getSingleResult();

You can read more about this pattern on the
`MongoDB <http://www.mongodb.org/display/DOCS/Trees+in+MongoDB#TreesinMongoDB-ChildLinks>`_
website!

Array of Ancestors
------------------

.. code-block:: php

    <?php
    /** @MappedSuperclass */
    class BaseCategory
    {
        /** @String */
        private $name;
    
        // ...
    }
    
    /** @Document */
    class Category extends BaseCategory
    {
        /** @Id */
        private $id;
    
        /**
         * @ReferenceMany
         * @Index
         */
        private $ancestors = array();
    
        /**
         * @ReferenceOne
         * @Index
         */
        private $parent;
    
        // ...
    }
    
    /** @EmbeddedDocument */
    class SubCategory extends BaseCategory
    {
    }

Query for all descendants of a category:

.. code-block:: php

    <?php
    $categories = $dm->createQuery('Category')
        ->field('ancestors.$id')->equals(new \MongoId('theid'))
        ->execute();

Query for all ancestors of a category:

.. code-block:: php

    <?php
    $category = $dm->createQuery('Category')
        ->field('id')->equals('theid')
        ->getSingleResult();
    $ancestors = $category->getAncestors();

You can read more about this pattern on the
`MongoDB <http://www.mongodb.org/display/DOCS/Trees+in+MongoDB#TreesinMongoDB-ArrayofAncestors>`_
website!

Materialized Paths
------------------

.. code-block:: php

    <?php
    /** @Document */
    class Category
    {
        /** @Id */
        private $id;
    
        /** @String */
        private $name;
    
        /** @String */
        private $path;
    
        // ...
    }

Query for the entire tree:

.. code-block:: php

    <?php
    $categories = $dm->createQuery('Category')
        ->sort('path', 'asc')
        ->execute();

Query for the node 'b' and all its descendants:

.. code-block:: php

    <?php
    $categories = $dm->createQuery('Category')
        ->field('path')->equals('/^a,b,/')
        ->execute();

You can read more about this pattern on the
`MongoDB <http://www.mongodb.org/display/DOCS/Trees+in+MongoDB#TreesinMongoDB-MaterializedPaths%28FullPathinEachNode%29>`_
website!


