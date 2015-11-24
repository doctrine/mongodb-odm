Filters
=======

Doctrine features a filter system that allows the developer to add additional
criteria to queries, regardless of where the query is generated within the
application (e.g. from a query builder, loading referenced documents). This is
useful for excluding documents at a low level, to ensure that they are neither
returned from MongoDB nor hydrated by ODM.

Example filter class
--------------------

Throughout this document, the example ``MyLocaleFilter`` class will be used to
illustrate how the filter feature works. A filter class must extend the base
``Doctrine\ODM\MongoDB\Query\Filter\BsonFilter`` class and implement the
``addFilterCriteria()`` method. This method receives ``ClassMetadata`` and is
invoked whenever a query is prepared for any class. Since filters are typically
designed with a specific class or interface in mind, ``addFilterCriteria()``
will frequently start by checking ``ClassMetadata`` and returning immediately if
it is not supported.

Parameters for the query should be set on the filter object by calling the
``BsonFilter::setParameter()`` method. Within the filter class, parameters
should be accessed via ``BsonFilter::getParameter()``.

.. code-block:: php

    <?php

    namespace Vendor\Filter;

    use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
    use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

    class MyLocaleFilter extends BsonFilter
    {
        public function addFilterCriteria(ClassMetadata $targetDocument)
        {
            // Check if the entity implements the LocalAware interface
            if ( ! $targetDocument->reflClass->implementsInterface('LocaleAware')) {
                return array();
            }

            return array('locale' => $this->getParameter('locale'));
        }
    }

Configuration
-------------
Filter classes are added to the configuration as following:

.. code-block:: php

    <?php

    $config->addFilter('locale', '\Vendor\Filter\MyLocaleFilter');

The ``Configuration#addFilter()`` method takes a name for the filter and the
name of the filter class, which will be constructed as necessary.

An optional third parameter may be used to set parameters at configuration time:

.. code-block:: php

    <?php

    $config->addFilter('locale', '\Vendor\Filter\MyLocaleFilter', array('locale' => 'en'));

Disabling/Enabling Filters and Setting Parameters
-------------------------------------------------

Filters can be disabled and enabled via the ``FilterCollection``, which is
stored in the ``DocumentManager``. The ``FilterCollection#enable($name)`` method
may be used to enabled and return a filter, after which you may set parameters.

.. code-block:: php

    <?php

    $filter = $dm->getFilterCollection()->enable("locale");
    $filter->setParameter('locale', array('$in' => array('en', 'fr'));

    // Disable the filter (perhaps temporarily to run an unfiltered query)
    $filter = $dm->getFilterCollection()->disable("locale");

.. warning::

    Disabling and enabling filters has no effect on managed documents. If you
    want to refresh or reload an object after having modified a filter or the
    FilterCollection, then you should clear the DocumentManager and re-fetch
    your documents so the new filtering rules may be applied.
