XML Mapping
===========

The XML mapping driver enables you to provide the ODM metadata in
form of XML documents.

The XML driver is backed by an XML Schema document that describes
the structure of a mapping document. The most recent version of the
XML Schema document is available online at
`http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd <http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd>`_.
The most convenient way to work with XML mapping files is to use an
IDE/editor that can provide code-completion based on such an XML
Schema document. The following is an outline of a XML mapping
document with the proper xmlns/xsi setup for the latest code in
trunk.

.. code-block:: xml

    <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
    
        ...
    
    </doctrine-mongo-mapping>

The XML mapping document of a class is loaded on-demand the first
time it is requested and subsequently stored in the metadata cache.
In order to work, this requires certain conventions:


- 
   Each document/mapped superclass must get its own dedicated XML
   mapping document.
- 
   The name of the mapping document must consist of the fully
   qualified name of the class, where namespace separators are
   replaced by dots (.).
- 
   All mapping documents should get the extension ".dcm.xml" to
   identify it as a Doctrine mapping file. This is more of a
   convention and you are not forced to do this. You can change the
   file extension easily enough.

.. code-block:: php

    <?php

    $driver->setFileExtension('.xml');

It is recommended to put all XML mapping documents in a single
folder but you can spread the documents over several folders if you
want to. In order to tell the XmlDriver where to look for your
mapping documents, supply an array of paths as the first argument
of the constructor, like this:

.. code-block:: php

    <?php

    // $config instanceof Doctrine\ODM\MongoDB\Configuration
    $driver = new XmlDriver(array('/path/to/files'));
    $config->setMetadataDriverImpl($driver);

Example
-------

As a quick start, here is a small example document that makes use
of several common elements:

.. code-block:: xml

    // Documents.User.dcm.xml

    <?xml version="1.0" encoding="UTF-8"?>
    
    <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/orm/doctrine-mongo-mapping.xsd">
    
        <document name="Documents\User" db="documents" collection="users">
            <field fieldName="id" id="true" />
            <field fieldName="username" name="login" type="string" />
            <field fieldName="email" type="string" unique="true" order="desc" />
            <field fieldName="createdAt" type="date" />
            <indexes>
                <index unique="true" dropDups="true">
                    <key name="username" order="desc">
                    <option name="safe" value="true" />
                </index>
            </indexes>
            <embed-one target-document="Documents\Address" field="address" />
            <reference-one target-document="Documents\Profile" field="profile">
                <cascade>
                    <all />
                </cascade>
            </reference-one>
            <embed-many target-document="Documents\Phonenumber" field="phonenumbers" />
            <reference-many target-document="Documents\Group" field="groups">
                <cascade>
                    <all />
                </cascade>
            </reference-many>
            <reference-one target-document="Documents\Account" field="account">
                <cascade>
                    <all />
                </cascade>
            </reference-one>
        </document>
    </doctrine-mongo-mapping>

Be aware that class-names specified in the XML files should be fully qualified.