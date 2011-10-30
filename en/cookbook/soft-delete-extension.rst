Soft Delete Extension
=====================

Sometimes you may not want to delete data from your database completely, but you want to
disable or temporarily delete some records so they do not appear anymore in your frontend.
Then, later you might want to restore that deleted data like it was never deleted.

This is possible with the ``SoftDelete`` extension which can be found on `github`_.

Installation
------------

First you just need to get the code by cloning the `github`_ repository:

.. code-block:: console

    $ git clone git://github.com/doctrine/mongodb-odm-softdelete.git

Now once you have the code you can setup the autoloader for it:

.. code-block:: php

    <?php

    $classLoader = new ClassLoader('Doctrine\ODM\MongoDB\SoftDelete', 'mongodb-odm-softdelete/lib');
    $classLoader->register();

Setup
-----

Now you can autoload the classes you need to setup the ``SoftDeleteManager`` instance you need to manage
the soft delete state of your documents:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\SoftDelete\Configuration;
    use Doctrine\ODM\MongoDB\SoftDelete\UnitOfWork;
    use Doctrine\ODM\MongoDB\SoftDelete\SoftDeleteManager;
    use Doctrine\Common\EventManager;

    // $dm is a DocumentManager instance we should already have

    $config = new Configuration();
    $evm = new EventManager();
    $sdm = new SoftDeleteManager($dm, $config, $evm);

SoftDeleteable Interface
------------------------

In order for your documents to work with the SoftDelete functionality they must implement
the ``SoftDeleteable`` interface:

.. code-block:: php

    <?php

    interface SoftDeleteable
    {
        function getDeletedAt();
    }

Example Implementation
----------------------

An implementation might look like this in a ``User`` document:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\SoftDelete\SoftDeleteable;

    /** @mongodb:Document */
    class User implements SoftDeleteable
    {
        // ...

        /** @mongodb:Date @mongodb:Index */
        private $deletedAt;

        public function getDeletedAt()
        {
            return $this->deletedAt;
        }

        // ...
    }

Usage
-----

Once you have the ``$sdm`` you can start managing the soft delete state of your documents:

.. code-block:: php

    <?php

    $jwage = $dm->getRepository('User')->findOneByUsername('jwage');
    $fabpot = $dm->getRepository('User')->findOneByUsername('fabpot');
    $sdm->delete($jwage);
    $sdm->delete($fabpot);
    $sdm->flush();

The call to ``SoftDeleteManager#flush()`` would persist the deleted state to the database
for all the documents it knows about and run a query like the following:

.. code-block:: javascript

    db.users.update({ _id : { $in : userIds }}, { $set : { deletedAt : new Date() } })

Now if we were to restore the documents:

.. code-block:: php

    <?php

    $sdm->restore($jwage);
    $sdm->flush();

It would execute a query like the following:

.. code-block:: javascript

    db.users.update({ _id : { $in : userIds }}, { $unset : { deletedAt : true } })

Events
------

We trigger some additional lifecycle events when documents are soft deleted and restored:

- Events::preSoftDelete
- Events::postSoftDelete
- Events::preRestore
- Events::postRestore

Using the events is easy, just define a class like the following:

.. code-block:: php

    <?php

    class TestEventSubscriber implements \Doctrine\Common\EventSubscriber
    {
        public function preSoftDelete(LifecycleEventArgs $args)
        {
            $document = $args->getDocument();
            $sdm = $args->getSoftDeleteManager();
        }

        public function getSubscribedEvents()
        {
            return array(Events::preSoftDelete);
        }
    }

Now we just need to add the event subscriber to the EventManager:

.. code-block:: php

    <?php

    $eventSubscriber = new TestEventSubscriber();
    $evm->addEventSubscriber($eventSubscriber);

When we soft delete something the preSoftDelete() method will be invoked before any queries are sent
to the database:

.. code-block:: php

    <?php

    $sdm->delete($fabpot);
    $sdm->flush();

Cascading Soft Deletes
----------------------

You can easily implement cascading soft deletes by using events in a certain way. Imagine you have
a User and Post document and you want to soft delete a users posts when you delete him.

You just need to setup an event listener like the following:

.. code-block:: php

    <?php

    use Doctrine\Common\EventSubscriber;
    use Doctrine\ODM\MongoDB\SoftDelete\Event\LifecycleEventArgs;

    class CascadingSoftDeleteListener implements EventSubscriber
    {
        public function preSoftDelete(LifecycleEventArgs $args)
        {
            $sdm = $args->getSoftDeleteManager();
            $document = $args->getDocument();
            if ($document instanceof User) {
                $sdm->deleteBy('Post', array('user.id' => $document->getId()));
            }
        }

        public function preRestore(LifecycleEventArgs $args)
        {
            $sdm = $args->getSoftDeleteManager();
            $document = $args->getDocument();
            if ($document instanceof User) {
                $sdm->restoreBy('Post', array('user.id' => $document->getId()));
            }
        }

        public function getSubscribedEvents()
        {
            return array(
                Events::preSoftDelete,
                Events::preRestore
            );
        }
    }

Now when you delete an instance of User it will also delete any Post documents where they
reference the User being deleted. If you restore the User, his Post documents will also be restored.

.. _github: https://github.com/doctrine/mongodb-odm-softdelete
