Queryable Encryption
====================

This cookbook provides a tutorial on setting up and using Queryable Encryption
(QE) with Doctrine MongoDB ODM to protect sensitive data in your documents.

Introduction
------------

In many applications, you need to store sensitive information like social
security numbers, financial data, or personal details. MongoDB's Queryable
Encryption allows you to encrypt this data on the client-side, store it as
fully randomized encrypted data, and still run expressive queries on it. This
ensures that sensitive data is never exposed in an unencrypted state on the
server, in system logs, or in backups.

This tutorial will guide you through the process of securing a document's
fields using queryable encryption, from defining the document and configuring
the connection to storing and querying the encrypted data.

.. note::

    Queryable Encryption is only available on MongoDB Enterprise 7.0+ or
    MongoDB Atlas.

The Scenario
------------

We will model a ``Patient`` document that has an embedded ``PatientRecord``.
This record contains sensitive information:

- A Social Security Number (``ssn``), which we need to query for exact
  matches.
- A ``billingAmount``, which should support range queries.
- A ``billing`` object, which should be encrypted but not directly queryable.

Defining the Documents
----------------------

First, let's define our ``Patient``, ``PatientRecord``, and ``Billing``
classes. We use the :ref:`#[Encrypt] <encrypt_attribute>` attribute to mark
fields that require encryption.

.. code-block:: php

    <?php

    namespace Documents;

    use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
    use Doctrine\ODM\MongoDB\Mapping\EncryptQuery;

    #[ODM\Document]
    class Patient
    {
        #[ODM\Id]
        public string $id;

        #[ODM\EmbedOne(targetDocument: PatientRecord::class)]
        public PatientRecord $patientRecord;
    }

    #[ODM\EmbeddedDocument]
    class PatientRecord
    {
        /**
         * Encrypted with equality queries.
         * This allows us to find a patient by their exact SSN.
         */
        #[ODM\Field(type: 'string')]
        #[ODM\Encrypt(queryType: EncryptQuery::Equality)]
        public string $ssn;

        /**
         * The entire embedded document is encrypted as an object.
         * By not specifying a queryType, we make it non-queryable.
         */
        #[ODM\EmbedOne(targetDocument: Billing::class)]
        #[ODM\Encrypt]
        public Billing $billing;

        /**
         * Encrypted with range queries.
         * This allows us to query for billing amounts within a certain range.
         */
        #[ODM\Field(type: 'int')]
        #[Encrypt(queryType: EncryptQuery::Range, min: 0, max: 5000, sparsity: 1)]
        public int $billingAmount;
    }

    #[ODM\EmbeddedDocument]
    class Billing
    {
        #[ODM\Field(type: 'string')]
        public string $creditCardNumber;
    }

Configuration and Usage
-----------------------

The following example demonstrates how to configure the ``DocumentManager`` for
encryption and how to work with encrypted documents.

Step 1: Configure the DocumentManager
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

First, we configure the ``DocumentManager`` with ``autoEncryption`` options.
For more details on the available options, see the `MongoDB\Driver\Manager`_
documentation. We'll use the ``local`` KMS provider for simplicity. For this
provider, you need a 96-byte master key.
The following code will look for the key in a local file (``master-key.bin``)
and generate it if it doesn't exist. In a production environment, you should
use a non-local key management service (KMS).
For each field marked with ``#[Encrypt]``, the MongoDB driver will generate
a Data Encryption Key (DEK), encrypt it with the master key, and store it in
the key vault collection. In Doctrine ODM, the key vault collection is set
to ``<database>.datakeys`` by default, but you can change it using the
``keyVaultNamespace`` option.

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Configuration;
    use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
    use MongoDB\BSON\Binary;

    // For the local KMS provider, we need a 96-byte master key.
    // We'll store it in a local file. If the file doesn't exist, we generate
    // one. In a production environment, ensure this key file is properly
    // secured.
    $keyFile = __DIR__ . '/master-key.bin';
    if (!file_exists($keyFile)) {
        file_put_contents($keyFile, random_bytes(96));
    }
    $masterKey = new Binary(file_get_contents($keyFile), Binary::TYPE_GENERIC);

    $config = new Configuration();
    // Enable auto encryption and set the KMS provider.
    $config->setAutoEncryption([
        'keyVaultNamespace' => 'encryption.datakeys'
    ]);
    $config->setKmsProvider([
        'type' => 'local',
        'key' => new Binary($masterKey),
    ]);

    // Other configuration
    $config->setHydratorDir(__DIR__ . '/Hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setPersistentCollectionDir(__DIR__ . '/PersistentCollections');
    $config->setPersistentCollectionNamespace('PersistentCollections');
    $config->setDefaultDB('my_db');
    $config->setMetadataDriverImpl(new AttributeDriver([__DIR__]));

Step 2: Create the DocumentManager
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``MongoDB\Client`` will be instantiated with the options from the
configuration.

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\DocumentManager;
    use MongoDB\Client;

    $client = new Client(
        uri: 'mongodb://localhost:27017/',
        uriOptions: [],
        driverOptions: $config->getDriverOptions(),
    );
    $documentManager = DocumentManager::create($client, $config);

The ``driverOptions`` passed to the client contain the ``autoEncryption`` option
that was configured in the previous step.

Step 3: Create the Encrypted Collection
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Next, we use the ``SchemaManager`` to create the collection with the necessary
encryption metadata. To make the example re-runnable, we can drop the collection
first.

.. code-block:: php

    <?php

    $schemaManager = $documentManager->getSchemaManager();
    $schemaManager->dropDocumentCollection(Patient::class);
    $schemaManager->createDocumentCollection(Patient::class);

Step 4: Persist and Query Documents
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Finally, we can persist and query documents as usual. The encryption and
decryption will be handled automatically.

.. code-block:: php

    <?php

    $patient = new Patient();
    $patient->patientRecord = new PatientRecord();
    $patient->patientRecord->ssn = '123-456-7890';
    $patient->patientRecord->billingAmount = 1500;
    $patient->patientRecord->billing = new Billing();
    $patient->patientRecord->billing->creditCardNumber = '9876-5432-1098-7654';

    $documentManager->persist($patient);
    $documentManager->flush();
    $documentManager->clear();

    // Query the document using an encrypted field
    $foundPatient = $documentManager->getRepository(Patient::class)->findOneBy([
        'patientRecord.ssn' => '123-456-7890',
    ]);

    // The document is retrieved and its fields are automatically decrypted
    assert($foundPatient instanceof Patient);
    assert($foundPatient->patientRecord->billingAmount === 1500);

What the Document Looks Like in the Database
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When you inspect the document directly in the database (e.g., using ``mongosh``
or `MongoDB Compass`_), you will see that the fields marked with ``#[Encrypt]``
are stored as BSON binary data (subtype 6), not the original BSON type. The
driver also adds a ``__safeContent__`` field to the document. For more details,
see the `Queryable Encryption Fundamentals`_ in the MongoDB manual.

.. code-block:: js

    {
      "_id": ObjectId("..."),
      "patientRecord": {
        "ssn": Binary("...", 6),
        "billing": Binary("...", 6),
        "billingAmount": Binary("...", 6)
      },
      "__safeContent__": [
        Binary("...", 0)
      ]
    }

Limitations
-----------

- The ODM simplifies configuration by supporting a single KMS provider per
  ``DocumentManager`` through ``Configuration::setKmsProvider()``. If you need
  to work with multiple KMS providers, you must manually configure the
  ``kmsProviders`` array and pass it as a driver option, bypassing the ODM's
  helper method.
- Automatic generation of the ``encryptedFieldsMap`` is not compatible with
  ``SINGLE_COLLECTION`` inheritance. Because all classes in the hierarchy
  share a single collection, the ``SchemaManager`` cannot merge their encrypted
  fields before creating the collection.
- Embedded documents and collections are encrypted as a whole. As such,
  they cannot be partially updated. Only the ``set*`` and ``atomicSet*``
  collection strategies can be used.
- For a complete list of limitations, please refer to the official
  `Queryable Encryption Limitations`_ documentation.

.. _MongoDB\Driver\Manager: https://www.php.net/manual/en/mongodb-driver-manager.construct.php#mongodb-driver-manager.construct-autoencryption
.. _MongoDB Compass: https://www.mongodb.com/products/compass
.. _Queryable Encryption Fundamentals: https://www.mongodb.com/docs/manual/core/queryable-encryption/fundamentals/#behavior
.. _Queryable Encryption Limitations: https://www.mongodb.com/docs/manual/core/queryable-encryption/reference/limitations/
