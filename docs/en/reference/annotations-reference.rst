:orphan:
Attributes Reference
=====================

Doctrine Annotations are deprecated and replaced by native PHP attributes.
All the attributes listed on :doc:`Attributes Reference <attributes-reference>`
can be used as annotations.

Support for annotations will be removed in Doctrine MongoDB ODM 3.0.

If you are still using annotations, you can migrate your code to attributes by
following the guide below:

Change the metadata driver configuration to use the ``AttributeDriver``:

.. code-block:: diff

    - use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationsDriver;
    + use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;

    - $config->setMetadataDriverImpl(AnnotationsDriver::create(__DIR__ . '/Documents'));
    + $config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/Documents'));

Replace the ``@ODM\Document`` annotations with the ``#[ODM\Document]`` attribute.

.. code-block:: diff

    use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

    - /**
    -  * @ODM\Document
    -  */
    + #[ODM\Document]
    class User
    {
    -    /**
    -     * @ODM\Id
    -     */
    +     #[ODM\Id]
        public string $id;

    -    /**
    -     * @ODM\Field(type="string")
    -     */
    +     #[ODM\Field(type: "string")]
        public string $name;
    }

.. note::

   You can use Rector to automate the migration process. See
   `How to Upgrade Annotations to Attributes`_ for more information.

.. _How to Upgrade Annotations to Attributes: https://getrector.com/blog/how-to-upgrade-annotations-to-attributes
