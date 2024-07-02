Keeping Your Modules Independent
================================

If you work with independent modules, you may encounter the problem of creating
relationships between objects in different modules. This is problematic because
it creates a dependency between the modules. This can be resolved by using
interfaces or abstract classes to define the relationships between the objects
and then using the ``ResolveTargetDocumentListener``. This event listener will
intercept certain calls inside Doctrine and rewrite ``targetDocument``
parameters in your metadata mapping at runtime. It will also rewrite class names
when no mapping metadata has been found for the original class name.

Background
----------

In the following example, we have an ``InvoiceModule`` that provides invoicing
functionality, and a ``CustomerModule`` that contains customer management tools.
We want to keep these separated, because they can be used in other systems
without each other; however, we'd like to use them together in our application.

In this case, we have an ``Invoice`` document with a relationship to a
non-existent object, an ``InvoiceSubjectInterface``. The goal is to get
the ``ResolveTargetDocumentListener`` to replace any mention of the interface
with a real class that implements that interface.

Configuration
-------------

We're going to use the following basic documents to explain how to set up and
use the ``ResolveTargetDocumentListener``.

A ``Customer`` class in the ``CustomerModule``. This class will be extended in
the application:

.. code-block:: php

    <?php

    namespace Acme\CustomerModule\Document;

    #[Document]
    class Customer
    {
        #[Id]
        public string $id;

        #[Field]
        public string $name;
    }

An ``Invoice`` document in the ``InvoiceModule``:

.. code-block:: php

    <?php

    namespace Acme\InvoiceModule\Document;

    use Acme\InvoiceModule\Model\InvoiceSubjectInterface;

    #[Document]
    class Invoice
    {
        #[Id]
        public string $id;

        #[ReferenceOne]
        public InvoiceSubjectInterface $subject;
    }

This class has a reference to an ``InvoiceSubjectInterface``. This interface
contains the list of methods that the ``InvoiceModule`` will need to access on
the subject so that we are sure that we have access to those methods. This
interface is also defined in the ``InvoiceModule``:

.. code-block:: php

    <?php

    namespace Acme\InvoiceModule\Model;

    interface InvoiceSubjectInterface
    {
        public function getName(): string;
    }

In the application, the ``Customer`` document class extends the ``Customer``
class from the ``CustomerModule`` and implements the ``InvoiceSubjectInterface``
from the ``InvoiceModule``. In most circumstances, only a single document class
should implement the ``InvoiceSubjectInterface``.
The ``ResolveTargetDocumentListener`` can only change the target to a single
object.

.. code-block:: php

    <?php

    namespace App\Document;

    use Acme\CustomerModule\Document\Customer as BaseCustomer;
    use Acme\InvoiceModule\Model\InvoiceSubjectInterface;

    #[Document]
    class Customer extends BaseCustomer implements InvoiceSubjectInterface
    {
        public function getName(): string
        {
            return $this->name;
        }
    }

Next, we need to configure a ``ResolveTargetDocumentListener`` to resolve to the
``Customer`` class of the application when an instance of
``InvoiceSubjectInterface`` from ``InvoiceModule`` is expected. This must be
done in the bootstrap code of your application. This is usually done before the
instantiation of the ``DocumentManager``:

.. code-block:: php

    <?php
    $evm  = new \Doctrine\Common\EventManager();
    $rtdl = new \Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener();

    // Adds a target-document class
    $rtdl->addResolveTargetDocument(
        \Acme\InvoiceModule\Model\InvoiceSubjectInterface::class,
        \App\Document\Customer::class,
        []
    );

    // Add the ResolveTargetDocumentListener
    $evm->addEventSubscriber($rtdl);

    // Create the document manager as you normally would
    $dm = \Doctrine\ODM\MongoDB\DocumentManager::create(null, $config, $evm);

With this configuration, you can create an ``Invoice`` document and set the
``subject`` property to a ``Customer`` document. When the invoice is retrieved
from the database, the ``subject`` property will be an instance of
``Customer``.

.. code-block:: php

    <?php

    use Acme\InvoiceModule\Document\Invoice;
    use App\Document\Customer;

    $customer         = new Customer();
    $customer->name   = 'Example Customer';
    $invoice          = new Invoice();
    $invoice->subject = $customer;

    $dm->persist($customer);
    $dm->persist($invoice);
    $dm->flush();
    $dm->clear();

    // Retrieve the invoice from the database
    $invoice = $dm->find(Invoice::class, $invoice->id);

    // The subject property will be an instance of Customer
    echo $invoice->subject->getName();


Final Thoughts
--------------

With ``ResolveTargetDocumentListener``, we are able to decouple our modules so
that they are usable by themselves and easier to maintain independently, while
still being able to define relationships between different objects across
modules.
