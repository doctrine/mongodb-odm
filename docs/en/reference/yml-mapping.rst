YAML Mapping
============

The YAML mapping driver enables you to provide the ODM metadata in
form of YAML documents.

The YAML mapping document of a class is loaded on-demand the first
time it is requested and subsequently stored in the metadata cache.
In order to work, this requires certain conventions:

-
   Each document/mapped superclass must get its own dedicated YAML
   mapping document.
-
   The name of the mapping document must consist of the fully
   qualified name of the class, where namespace separators are
   replaced by dots (.).
-
   All mapping documents should get the extension ".dcm.yml" to
   identify it as a Doctrine mapping file. This is more of a
   convention and you are not forced to do this. You can change the
   file extension easily enough.

-

.. code-block:: php

    <?php

    $driver->setFileExtension('.yml');

It is recommended to put all YAML mapping documents in a single
folder but you can spread the documents over several folders if you
want to. In order to tell the YamlDriver where to look for your
mapping documents, supply an array of paths as the first argument
of the constructor, like this:

.. code-block:: php

    <?php

    // $config instanceof Doctrine\ODM\MongoDB\Configuration
    $driver = new YamlDriver(array('/path/to/files'));
    $config->setMetadataDriverImpl($driver);

Simplified YAML Driver
~~~~~~~~~~~~~~~~~~~~~~

The Symfony project sponsored a driver that simplifies usage of the YAML Driver.
The changes between the original driver are:

1. File Extension is .mongodb-odm.yml
2. Filenames are shortened, "MyProject\\Documents\\User" will become User.mongodb-odm.yml
3. You can add a global file and add multiple documents in this file.

Configuration of this client works a little bit different:

.. code-block:: php

    <?php
    $namespaces = array(
        '/path/to/files1' => 'MyProject\Documents',
        '/path/to/files2' => 'OtherProject\Documents'
    );
    $driver = new \Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedYamlDriver($namespaces);
    $driver->setGlobalBasename('global'); // global.mongodb-odm.yml

Example
-------

As a quick start, here is a small example document that makes use
of several common elements:

.. code-block:: yaml

    # Documents.User.dcm.yml

    Documents\User:
      db: documents
      collection: user
      fields:
        id:
          id: true
        username:
          name: login
          type: string
        email:
          unique:
            order: desc
        createdAt:
          type: date
      indexes:
        index1:
          keys:
            username: desc
          options:
            unique: true
            dropDups: true
            safe: true
      embedOne:
        address:
          targetDocument: Documents\Address
      embedMany:
        phonenumbers:
          targetDocument: Documents\Phonenumber
      referenceOne:
        profile:
          targetDocument: Documents\Profile
          cascade: all
        account:
          targetDocument: Documents\Account
          cascade: all
      referenceMany:
        groups:
          targetDocument: Documents\Group
          cascade: all

    # Alternative syntax for the exact same example
    # (allows custom key name for embedded document and reference).
    Documents\User:
      db: documents
      collection: user
      fields:
        id:
          id: true
        username:
          name: login
          type: string
        email:
          unique:
            order: desc
        createdAt:
          type: date
        address:
          embedded: true
          type: one
          targetDocument: Documents\Address
        phonenumbers:
          embedded: true
          type: many
          targetDocument: Documents\Phonenumber
        profile:
          reference: true
          type: one
          targetDocument: Documents\Profile
          cascade: all
        account:
          reference: true
          type: one
          targetDocument: Documents\Account
          cascade: all
        groups:
          reference: true
          type: many
          targetDocument: Documents\Group
          cascade: all
      indexes:
        index1:
          keys:
            username: desc
          options:
            unique: true
            dropDups: true
            safe: true

Be aware that class-names specified in the YAML files should be fully qualified.

.. note::

    The ``name`` property is an optional setting to change  name of the field
    **in the database**. Specifying it is optional and defaults to the name
    of mapped field.
