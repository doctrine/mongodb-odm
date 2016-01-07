Simple Search Engine
====================

It is very easy to implement a simple keyword search engine with MongoDB. Because of
its flexible schema less nature we can store the keywords we want to search through directly
on the document. MongoDB is capable of indexing the embedded documents so the results are fast
and scalable.

Sample Model: Product
---------------------

Imagine you had a ``Product`` document and you want to search the products by keywords. You can
setup a document like the following with a ``$keywords`` property that is mapped as a collection:

.. code-block:: php

    <?php

    namespace Documents;

    /** @Document */
    class Product
    {
        /** @Id */
        private $id;

        /** @Field(type="string") */
        private $title;

        /** @Field(type="collection") @Index */
        private $keywords = array();

        // ...
    }

Working with Keywords
---------------------

Now, create a product and add some keywords:

.. code-block:: php

    <?php

    $product = new Product();
    $product->setTitle('Nike Air Jordan 2011');
    $product->addKeyword('nike shoes');
    $product->addKeyword('jordan shoes');
    $product->addKeyword('air jordan');
    $product->addKeyword('shoes');
    $product->addKeyword('2011');

    $dm->persist($product);
    $dm->flush();

The above example populates the keywords manually but you could very easily write some code which
automatically generates your keywords from a string built by the Product that may include the title,
description and other fields. You could also use a tool like the `AlchemyAPI`_ if you want to do
some more intelligent keyword extraction.

Searching Keywords
------------------

Searching the keywords in the ``Product`` collection is easy! You can run a query like the following
to find documents that have at least one of the keywords:

.. code-block:: php

    <?php

    $keywords = array('nike shoes', 'air jordan');

    $qb = $dm->createQueryBuilder('Product')
        ->field('keywords')->in($keywords);

You can make the query more strict by using the ``all()`` method instead of ``in()``:

.. code-block:: php

    <?php

    $keywords = array('nike shoes', 'air jordan');

    $qb = $dm->createQueryBuilder('Product')
        ->field('keywords')->all($keywords);

The above query would only return products that have both of the keywords!

User Input
~~~~~~~~~~

You can easily build keywords from a user search form by exploding whitespace and passing
the results to your query. Here is an example:

.. code-block:: php

    <?php

    $queryString = $_REQUEST['q'];
    $keywords = explode(' ', $queryString);

    $qb = $dm->createQueryBuilder('Product')
        ->field('keywords')->all($keywords);

Embedded Documents
------------------

If you want to use an embedded document instead of just an array then you can. It will allow you to store
additional information with each keyword, like its weight.

Definition
~~~~~~~~~~

You can setup a ``Keyword`` document like the following:

.. code-block:: php

    <?php

    /** @EmbeddedDocument */
    class Keyword
    {
        /** @Field(type="string") @Index */
        private $keyword;

        /** @Field(type="int") */
        private $weight;

        public function __construct($keyword, $weight)
        {
            $this->keyword = $keyword;
            $this->weight = $weight;
        }

        // ...
    }

Now you can embed the ``Keyword`` document many times in the ``Product``:

.. code-block:: php

    <?php

    namespace Documents;

    /** @Document */
    class Product
    {
        // ...

        /** @EmbedMany(targetDocument="Keyword") */
        private $keywords;

        // ...
    }

With the new embedded document to add a keyword to a ``Product`` the API is a little different,
you would have to do the following:

.. code-block:: php

    <?php

    $product->addKeyword(new Keyword('nike shoes', 1));

This is a very basic search engine example and can work for many small and simple applications. If you
need better searching functionality you can look at integrating something like `Solr`_ in your project.

.. _AlchemyAPI: http://www.alchemyapi.com
.. _Solr: http://lucene.apache.org/solr
