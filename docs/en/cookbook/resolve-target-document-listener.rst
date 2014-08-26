Keeping Your Modules Independent
================================

One of the goals of using modules is to create discrete units of functionality
that do not have many (if any) dependencies, allowing you to use that
functionality in other applications without including unnecessary items.

Doctrine MongoDB ODM includes a utility called
``ResolveTargetDocumentListener``, that functions by intercepting certain calls
inside Doctrine and rewriting ``targetDocument`` parameters in your metadata
mapping at runtime. This allows your bundle to use an interface or abstract
class in its mappings while still allowing the mapping to resolve to a concrete
document class at runtime.

This functionality allows you to define relationships between different
documents without creating hard dependencies.

Background
----------

In the following example, we have an `InvoiceModule` that provides invoicing
functionality, and a `CustomerModule` that contains customer management tools.
We want to keep these separated, because they can be used in other systems
without each other; however, we'd like to use them together in our application.

In this case, we have an ``Invoice`` document with a relationship to a
non-existent object, an ``InvoiceSubjectInterface``. The goal is to get
the ``ResolveTargetDocumentListener`` to replace any mention of the interface
with a real class that implements that interface.

Configuration
-------------

We're going to use the following basic documents (which are incomplete
for brevity) to explain how to set up and use the
``ResolveTargetDocumentListener``.

A Customer document:

.. code-block:: php

    <?php
    // src/Acme/AppModule/Document/Customer.php

    namespace Acme\AppModule\Document;

    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
    use Acme\CustomerModule\Document\Customer as BaseCustomer;
    use Acme\InvoiceModule\Model\InvoiceSubjectInterface;

    /**
     * @ODM\Document
     */
    class Customer extends BaseCustomer implements InvoiceSubjectInterface
    {
        // In our example, any methods defined in the InvoiceSubjectInterface
        // are already implemented in the BaseCustomer
    }

An Invoice document:

.. code-block:: php

    <?php
    // src/Acme/InvoiceModule/Document/Invoice.php

    namespace Acme\InvoiceModule\Document;

    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
    use Acme\InvoiceModule\Model\InvoiceSubjectInterface;

    /**
     * @ODM\Document
     */
    class Invoice
    {
        /**
         * @ODM\ReferenceOne(targetDocument="Acme\InvoiceModule\Model\InvoiceSubjectInterface")
         * @var InvoiceSubjectInterface
         */
        protected $subject;
    }

An InvoiceSubjectInterface:

.. code-block:: php

    <?php
    // src/Acme/InvoiceModule/Model/InvoiceSubjectInterface.php

    namespace Acme\InvoiceModule\Model;

    /**
     * An interface that the invoice Subject object should implement.
     * In most circumstances, only a single object should implement
     * this interface as the ResolveTargetDocumentListener can only
     * change the target to a single object.
     */
    interface InvoiceSubjectInterface
    {
        // List any additional methods that your InvoiceModule
        // will need to access on the subject so that you can
        // be sure that you have access to those methods.

        /**
         * @return string
         */
        public function getName();
    }

Next, we need to configure the listener. Add this to the area where you setup
Doctrine MongoDB ODM. You must set this up in the way outlined below, otherwise
you cannot be guaranteed that the targetDocument resolution will occur reliably:

.. code-block:: php

    <?php
    $evm  = new \Doctrine\Common\EventManager;
    $rtdl = new \Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;

    // Adds a target-document class
    $rtdl->addResolveTargetDocument(
        'Acme\\InvoiceModule\\Model\\InvoiceSubjectInterface',
        'Acme\\CustomerModule\\Document\\Customer',
        array()
    );

    // Add the ResolveTargetDocumentListener
    $evm->addEventListener(\Doctrine\ODM\MongoDB\Events::loadClassMetadata, $rtdl);

    // Create the document manager as you normally would
    $dm = \Doctrine\ODM\MongoDB\DocumentManager::create($connectionOptions, $config, $evm);

Final Thoughts
--------------

With ``ResolveTargetDocumentListener``, we are able to decouple our bundles so
that they are usable by themselves and easier to maintain independently, while
still being able to define relationships between different objects.
