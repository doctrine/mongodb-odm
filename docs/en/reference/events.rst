Events
======

Doctrine features a lightweight event system that is part of the
Common package.

The Event System
----------------

The event system is controlled by the ``EventManager``. It is the
central point of Doctrine's event listener system. Listeners are
registered on the manager and events are dispatched through the
manager.

.. code-block:: php

    <?php

    $evm = new EventManager();

Now we can add some event listeners to the ``$evm``. Let's create a
``EventTest`` class to play around with.

.. code-block:: php

    <?php

    class EventTest
    {
        const preFoo = 'preFoo';
        const postFoo = 'postFoo';
    
        private $_evm;
    
        public $preFooInvoked = false;
        public $postFooInvoked = false;
    
        public function __construct($evm)
        {
            $evm->addEventListener(array(self::preFoo, self::postFoo), $this);
        }
    
        public function preFoo(EventArgs $e)
        {
            $this->preFooInvoked = true;
        }
    
        public function postFoo(EventArgs $e)
        {
            $this->postFooInvoked = true;
        }
    }
    
    // Create a new instance
    $test = new EventTest($evm);

Events can be dispatched by using the ``dispatchEvent()`` method.

.. code-block:: php

    <?php

    $evm->dispatchEvent(EventTest::preFoo);
    $evm->dispatchEvent(EventTest::postFoo);

You can easily remove a listener with the ``removeEventListener()``
method.

.. code-block:: php

    <?php

    $evm->removeEventListener(array(self::preFoo, self::postFoo), $this);

The Doctrine event system also has a simple concept of event
subscribers. We can define a simple ``TestEventSubscriber`` class
which implements the ``\Doctrine\Common\EventSubscriber`` interface
and implements a ``getSubscribedEvents()`` method which returns an
array of events it should be subscribed to.

.. code-block:: php

    <?php

    class TestEventSubscriber implements \Doctrine\Common\EventSubscriber
    {
        const preFoo = 'preFoo';
    
        public $preFooInvoked = false;
    
        public function preFoo()
        {
            $this->preFooInvoked = true;
        }
    
        public function getSubscribedEvents()
        {
            return array(self::preFoo);
        }
    }
    
    $eventSubscriber = new TestEventSubscriber();
    $evm->addEventSubscriber($eventSubscriber);

Now when you dispatch an event any event subscribers will be
notified for that event.

.. code-block:: php

    <?php

    $evm->dispatchEvent(TestEventSubscriber::preFoo);

Now test the ``$eventSubscriber`` instance to see if the
``preFoo()`` method was invoked.

.. code-block:: php

    <?php

    if ($eventSubscriber->preFooInvoked) {
        echo 'pre foo invoked!';
    }

.. _lifecycle_events:

Lifecycle Events
----------------

The DocumentManager and UnitOfWork trigger several events during
the life-time of their registered documents.

- 
   preRemove - The preRemove event occurs for a given document before
   the respective DocumentManager remove operation for that document
   is executed.
- 
   postRemove - The postRemove event occurs for a document after the
   document has been removed. It will be invoked after the database
   delete operations.
- 
   prePersist - The prePersist event occurs for a given document
   before the respective DocumentManager persist operation for that
   document is executed.
- 
   postPersist - The postPersist event occurs for a document after
   the document has been made persistent. It will be invoked after the
   database insert operations. Generated primary key values are
   available in the postPersist event.
- 
   preUpdate - The preUpdate event occurs before the database update
   operations to document data.
- 
   postUpdate - The postUpdate event occurs after the database update
   operations to document data.
-
   preLoad - The preLoad event occurs for a document before the
   document has been loaded into the current DocumentManager from the
   database or after the refresh operation has been applied to it.
-
   postLoad - The postLoad event occurs for a document after the
   document has been loaded into the current DocumentManager from the
   database or after the refresh operation has been applied to it.
- 
   loadClassMetadata - The loadClassMetadata event occurs after the
   mapping metadata for a class has been loaded from a mapping source
   (annotations/xml/yaml).
-
   preFlush - The preFlush event occurs before the change-sets of all
   managed documents are computed. This both a lifecycle call back and
   and listener.
-
   postFlush - The postFlush event occurs after the change-sets of all
   managed documents are computed.
- 
   onFlush - The onFlush event occurs after the change-sets of all
   managed documents are computed. This event is not a lifecycle
   callback.
-
   onClear - The onClear event occurs after the UnitOfWork has had
   its state cleared.
-
   documentNotFound - The documentNotFound event occurs when a proxy object
   could not be initialized. This event is not a lifecycle callback.
-
   postCollectionLoad - The postCollectionLoad event occurs just after
   collection has been initialized (loaded) and before new elements
   are re-added to it.

You can access the Event constants from the ``Events`` class in the
ODM package.

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Events;

    echo Events::preUpdate;

These can be hooked into by two different types of event
listeners:

- 
   Lifecycle Callbacks are methods on the document classes that are
   called when the event is triggered. They receive absolutely no
   arguments and are specifically designed to allow changes inside the
   document classes state.
- 
   Lifecycle Event Listeners are classes with specific callback
   methods that receives some kind of ``EventArgs`` instance which
   give access to the document, DocumentManager or other relevant
   data.

.. note::

    All Lifecycle events that happen during the ``flush()`` of
    a DocumentManager have very specific constraints on the allowed
    operations that can be executed. Please read the
    *Implementing Event Listeners* section very carefully to understand
    which operations are allowed in which lifecycle event.

Lifecycle Callbacks
-------------------

A lifecycle event is a regular event with the additional feature of
providing a mechanism to register direct callbacks inside the
corresponding document classes that are executed when the lifecycle
event occurs.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class User
    {
        // ...
    
        /**
         * @Field
         */
        public $value;
    
        /** @Field */
        private $createdAt;
    
        /** @PrePersist */
        public function doStuffOnPrePersist(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
            $this->createdAt = date('Y-m-d H:i:s');
        }
    
        /** @PrePersist */
        public function doOtherStuffOnPrePersist(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
            $this->value = 'changed from prePersist callback!';
        }
    
        /** @PostPersist */
        public function doStuffOnPostPersist(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
            $this->value = 'changed from postPersist callback!';
        }

        /** @PreLoad */
        public function doStuffOnPreLoad(\Doctrine\ODM\MongoDB\Event\PreLoadEventArgs $eventArgs)
        {
            $data =& $eventArgs->getData();
            $data['value'] = 'changed from preLoad callback';
        }
    
        /** @PostLoad */
        public function doStuffOnPostLoad(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
            $this->value = 'changed from postLoad callback!';
        }
    
        /** @PreUpdate */
        public function doStuffOnPreUpdate(\Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs $eventArgs)
        {
            $this->value = 'changed from preUpdate callback!';
        }

        /** @PreFlush */
        public function preFlush(\Doctrine\ODM\MongoDB\Event\PreFlushEventArgs $eventArgs)
        {
            $this->value = 'changed from preFlush callback!';
        }
    }

Note that when using annotations you have to apply the
@HasLifecycleCallbacks marker annotation on the document class.

Listening to Lifecycle Events
-----------------------------

Lifecycle event listeners are much more powerful than the simple
lifecycle callbacks that are defined on the document classes. They
allow to implement re-usable behaviours between different document
classes, yet require much more detailed knowledge about the inner
workings of the DocumentManager and UnitOfWork. Please read the
*Implementing Event Listeners* section carefully if you are trying
to write your own listener.

To register an event listener you have to hook it into the
EventManager that is passed to the DocumentManager factory:

.. code-block:: php

    <?php

    $eventManager = new EventManager();
    $eventManager->addEventListener(array(Events::preUpdate), new MyEventListener());
    $eventManager->addEventSubscriber(new MyEventSubscriber());
    
    $documentManager = DocumentManager::create($mongo, $config, $eventManager);

You can also retrieve the event manager instance after the
DocumentManager was created:

.. code-block:: php

    <?php

    $documentManager->getEventManager()->addEventListener(array(Events::preUpdate), new MyEventListener());
    $documentManager->getEventManager()->addEventSubscriber(new MyEventSubscriber());

Implementing Event Listeners
----------------------------

This section explains what is and what is not allowed during
specific lifecycle events of the UnitOfWork. Although you get
passed the DocumentManager in all of these events, you have to
follow this restrictions very carefully since operations in the
wrong event may produce lots of different errors, such as
inconsistent data and lost updates/persists/removes.

prePersist
~~~~~~~~~~

Listen to the ``prePersist`` event:

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::prePersist, $test);

Define the ``EventTest`` class:

.. code-block:: php

    <?php

    class EventTest
    {
        public function prePersist(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
            $document = $eventArgs->getDocument();
            $document->setSomething();
        }
    }

preLoad
~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::preLoad, $test);

Define the ``EventTest`` class with a ``preLoad()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function preLoad(\Doctrine\ODM\MongoDB\Event\PreLoadEventArgs $eventArgs)
        {
            $data =& $eventArgs->getData();
            // do something
        }
    }

postLoad
~~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::postLoad, $test);

Define the ``EventTest`` class with a ``postLoad()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function postLoad(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
            $document = $eventArgs->getDocument();
            // do something
        }
    }

preRemove
~~~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::preRemove, $test);

Define the ``EventTest`` class with a ``preRemove()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function preRemove(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
            $document = $eventArgs->getDocument();
            // do something
        }
    }

preFlush
~~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::preFlush, $test);

Define the ``EventTest`` class with a ``preFlush()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function preFlush(\Doctrine\ODM\MongoDB\Event\PreFlushEventArgs $eventArgs)
        {
            $dm = $eventArgs->getDocumentManager();
            $uow = $dm->getUnitOfWork();
            // do something
        }
    }

onFlush
~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::onFlush, $test);

Define the ``EventTest`` class with a ``onFlush()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function onFlush(\Doctrine\ODM\MongoDB\Event\OnFlushEventArgs $eventArgs)
        {
            $dm = $eventArgs->getDocumentManager();
            $uow = $dm->getUnitOfWork();
            // do something
        }
    }

postFlush
~~~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::postFlush, $test);

Define the ``EventTest`` class with a ``postFlush()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function postFlush(\Doctrine\ODM\MongoDB\Event\PostFlushEventArgs $eventArgs)
        {
            $dm = $eventArgs->getDocumentManager();
            $uow = $dm->getUnitOfWork();
            // do something
        }
    }

preUpdate
~~~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::preUpdate, $test);

Define the ``EventTest`` class with a ``preUpdate()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function preUpdate(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
            $document = $eventArgs->getDocument();
            $document->setSomething();
            $dm = $eventArgs->getDocumentManager();
            $class = $dm->getClassMetadata(get_class($document));
            $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($class, $document);
        }
    }

.. note::

    If you modify a document in the preUpdate event you must call ``recomputeSingleDocumentChangeSet``
    for the modified document in order for the changes to be persisted.

onClear
~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::onClear, $test);

Define the ``EventTest`` class with a ``onClear()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function onClear(\Doctrine\ODM\MongoDB\Event\OnClearEventArgs $eventArgs)
        {
            $class = $eventArgs->getDocumentClass();
            $dm = $eventArgs->getDocumentManager();
            $uow = $dm->getUnitOfWork();
            
            // Check if event clears all documents.
            if ($eventArgs->clearsAllDocuments()) {
                // do something
            }
            // do something
        }
    }

documentNotFound
~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::documentNotFound, $test);

Define the ``EventTest`` class with a ``documentNotFound()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function documentNotFound(\Doctrine\ODM\MongoDB\Event\DocumentNotFoundEventArgs $eventArgs)
        {
            $proxy = $eventArgs->getObject();
            $identifier = $eventArgs->getIdentifier();
            // do something
            // To prevent the documentNotFound exception from being thrown, call the disableException() method:
            $eventArgs->disableException();
        }
    }


postUpdate, postRemove, postPersist
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::postUpdate, $test);
    $evm->addEventListener(Events::postRemove, $test);
    $evm->addEventListener(Events::postPersist, $test);

Define the ``EventTest`` class with a ``postUpdate()``, ``postRemove()`` and ``postPersist()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function postUpdate(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
        }

        public function postRemove(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
        }

        public function postPersist(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $eventArgs)
        {
        }
    }

postCollectionLoad
~~~~~~~~~~~~~~~~~~

.. note::
    This event was introduced in version 1.1

.. code-block:: php

    <?php

    $test = new EventTest();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::postCollectionLoad, $test);

Define the ``EventTest`` class with a ``postCollectionLoad()`` method:

.. code-block:: php

    <?php

    class EventTest
    {
        public function postCollectionLoad(\Doctrine\ODM\MongoDB\Event\PostCollectionLoadEventArgs $eventArgs)
        {
            $collection = $eventArgs->getCollection();
            if ($collection instanceof \Malarzm\Collections\DiffableCollection) {
                $collection->snapshot();
            }
        }
    }


Load ClassMetadata Event
------------------------

When the mapping information for a document is read, it is
populated in to a ``ClassMetadata`` instance. You can hook in to
this process and manipulate the instance with the ``loadClassMetadata`` event:

.. code-block:: php

    <?php

    $test = new EventTest();
    $metadataFactory = $dm->getMetadataFactory();
    $evm = $dm->getEventManager();
    $evm->addEventListener(Events::loadClassMetadata, $test);
    
    class EventTest
    {
        public function loadClassMetadata(\Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs $eventArgs)
        {
            $classMetadata = $eventArgs->getClassMetadata();
            $fieldMapping = array(
                'fieldName' => 'about',
                'type' => 'string'
            );
            $classMetadata->mapField($fieldMapping);
        }
    }
