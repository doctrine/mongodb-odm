.. _custom_collection:

Custom Collections
==================

.. note::
    This feature was introduced in version 1.1

By default, Doctrine uses ``ArrayCollection`` implementation of its ``Collection``
interface to hold both embedded and referenced documents. That collection may then
be wrapped by a ``PersistentCollection`` to allow for change tracking and other
persistence-related features.

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;

    /** @Document */
    class Application
    {
        // ...

        /**
         * @EmbedMany(targetDocument="Section")
         */
        private $sections;

        public function __construct()
        {
            $this->sections = new ArrayCollection();
        }

        // ...
    }

For most cases this solution is sufficient but more sophisticated domains could use
their own collections (e.g. a collection that ensures its contained objects are sorted)
or to simply add common filtering methods that otherwise would otherwise be added to
owning document's class.

Custom Collection Classes
-------------------------

.. note::
    You may want to check `malarzm/collections <https://github.com/malarzm/collections>`_
    which provides alternative implementations of Doctrine's ``Collection`` interface and
    aims to kickstart development of your own collections.

Using your own ``Collection`` implementation is as simple as specifying the
``collectionClass`` parameter in the ``@EmbedMany`` or ``@ReferenceMany`` mapping
and ensuring that your custom class is initialized in the owning class' constructor:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;

    /** @Document */
    class Application
    {
        // ...

        /**
         * @EmbedMany(
         *  collectionClass="SectionCollection"
         *  targetDocument="Section"
         * )
         */
        private $sections;

        public function __construct()
        {
            $this->sections = new SectionCollection();
        }

        // ...
    }

If you are satisfied with ``ArrayCollection`` and only want
to sprinkle it with some filtering methods, you may just extend it:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;

    class SectionCollection extends ArrayCollection
    {
        public function getEnabled()
        {
            return $this->filter(function(Section $s) {
                return $s->isEnabled();
            });
        }
    }

Alternatively, you may want to implement the whole class from scratch:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\Collection;

    class SectionCollection implements Collection
    {
        private $elements = array();

        public function __construct(array $elements = array())
        {
            $this->elements = $elements;
        }

        // your implementation of all methods interface requires
    }

Taking Control of the Collection's Constructor
----------------------------------------------

By default, Doctrine assumes that it can instantiate your collections in same
manner as an ``ArrayCollection`` (i.e. the only parameter is an optional PHP
array); however, you may want to inject additional dependencies into your
custom collection class(es). This will require you to create a
`PersistentCollectionFactory implementation <https://github.com/doctrine/mongodb-odm/blob/master/lib/Doctrine/ODM/MongoDB/PersistentCollection/PersistentCollectionFactory.php>`_,
which Doctrine will then use to construct its persistent collections.
You may decide to implement this class from scratch or extend our
``AbstractPersistentCollectionFactory``:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\PersistentCollection\AbstractPersistentCollectionFactory;
    use Symfony\Component\EventDispatcher\EventDispatcherInterface;

    final class YourPersistentCollectionFactory extends AbstractPersistentCollectionFactory
    {
        private $eventDispatcher;

        public function __construct(EventDispatcherInterface $eventDispatcher)
        {
            $this->eventDispatcher = $eventDispatcher;
        }

        protected function createCollectionClass($collectionClass)
        {
            switch ($collectionClass) {
                case SectionCollection::class:
                    return new $collectionClass(array(), $this->eventDispatcher);
                default:
                    return new $collectionClass;
            }
        }
    }

The factory class must then be registered in the ``Configuration``:

.. code-block:: php

    <?php

    $eventDispatcher = $container->get('event_dispatcher');
    $collFactory = new YourPersistentCollectionFactory($eventDispatcher);
    $configuration = new Configuration();
    // your other config here
    $configuration->setPersistentCollectionFactory($collFactory);
