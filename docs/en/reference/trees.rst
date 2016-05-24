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
    
        /** @Field(type="string") */
        private $title;
    
        /** @Field(type="string") */
        private $body;
    
        /** @EmbedMany(targetDocument="Comment") */
        private $comments = array();
    
        // ...
    }
    
    /** @EmbeddedDocument */
    class Comment
    {
        /** @Field(type="string") */
        private $by;
    
        /** @Field(type="string") */
        private $text;
    
        /** @EmbedMany(targetDocument="Comment") */
        private $replies = array();
    
        // ...
    }

Retrieve a blog post and only select the first 10 comments:

.. code-block:: php

    <?php

    $post = $dm->createQueryBuilder('BlogPost')
        ->selectSlice('replies', 0, 10)
        ->getQuery()
        ->getSingleResult();

    $replies = $post->getReplies();

You can read more about this pattern on the MongoDB documentation page "Trees in MongoDB" in the
`Full Tree in Single Document <http://www.mongodb.org/display/DOCS/Trees+in+MongoDB#TreesinMongoDB-FullTreeinSingleDocument>`_ section.

Parent Reference
----------------

.. code-block:: php

    <?php

    /** @Document */
    class Category
    {
        /** @Id */
        private $id;
    
        /** @Field(type="string") */
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

    $children = $dm->createQueryBuilder('Category')
        ->field('parent.id')->equals('theid')
        ->getQuery()
        ->execute();

You can read more about this pattern on the MongoDB documentation page "Trees in MongoDB" in the
`Parent Links <https://docs.mongodb.com/manual/tutorial/model-tree-structures/#model-tree-structures-with-parent-references>`_ section.

Child Reference
---------------

.. code-block:: php

    <?php

    /** @Document */
    class Category
    {
        /** @Id */
        private $id;
    
        /** @Field(type="string") */
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

    $category = $dm->createQueryBuilder('Category')
        ->field('id')->equals('theid')
        ->getQuery()
        ->getSingleResult();

    $children = $category->getChildren();

Query for immediate parent of a category:

.. code-block:: php

    <?php

    $parent = $dm->createQueryBuilder('Category')
        ->field('children.id')->equals('theid')
        ->getQuery()
        ->getSingleResult();

You can read more about this pattern on the MongoDB documentation page "Trees in MongoDB" in the
`Child Links <https://docs.mongodb.com/manual/tutorial/model-tree-structures/#model-tree-structures-with-child-references>`_ section.

Array of Ancestors
------------------

.. code-block:: php

    <?php

    /** @MappedSuperclass */
    class BaseCategory
    {
        /** @Field(type="string") */
        private $name;
    
        // ...
    }
    
    /** @Document */
    class Category extends BaseCategory
    {
        /** @Id */
        private $id;
    
        /**
         * @ReferenceMany(targetDocument="Category")
         * @Index
         */
        private $ancestors = array();
    
        /**
         * @ReferenceOne(targetDocument="Category")
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

    $categories = $dm->createQueryBuilder('Category')
        ->field('ancestors.id')->equals('theid')
        ->getQuery()
        ->execute();

Query for all ancestors of a category:

.. code-block:: php

    <?php

    $category = $dm->createQuery('Category')
        ->field('id')->equals('theid')
        ->getQuery()
        ->getSingleResult();

    $ancestors = $category->getAncestors();

You can read more about this pattern on the MongoDB documentation page "Trees in MongoDB" in the
`Array of Ancestors <https://docs.mongodb.com/manual/tutorial/model-tree-structures/#model-tree-structures-with-an-array-of-ancestors>`_ section.

Materialized Paths
------------------

.. code-block:: php

    <?php

    /** @Document */
    class Category
    {
        /** @Id */
        private $id;
    
        /** @Field(type="string") */
        private $name;
    
        /** @Field(type="string") */
        private $path;
    
        // ...
    }

Query for the entire tree:

.. code-block:: php

    <?php

    $categories = $dm->createQuery('Category')
        ->sort('path', 'asc')
        ->getQuery()
        ->execute();

Query for the node 'b' and all its descendants:

.. code-block:: php

    <?php
    $categories = $dm->createQuery('Category')
        ->field('path')->equals('/^a,b,/')
        ->getQuery()
        ->execute();

You can read more about this pattern on the MongoDB documentation page "Trees in MongoDB" in the
`Materialized Paths (Full Path in Each Node) <https://docs.mongodb.com/manual/tutorial/model-tree-structures/#model-tree-structures-with-materialized-paths>`_ section.
