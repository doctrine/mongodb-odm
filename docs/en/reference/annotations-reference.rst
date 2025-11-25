:orphan:
Attributes Reference
=====================

Doctrine Annotations replaced by native PHP attributes, see :doc:`Attributes Reference <attributes-reference>`.

If you are still using annotations, you can migrate your code to attributes by
following the guide below:

Change the metadata driver configuration to use the ``AttributeDriver``:

.. code-block:: diff

    - use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationsDriver;
    + use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;

    - $config->setMetadataDriverImpl(AnnotationsDriver::create(__DIR__ . '/Documents'));
    + $config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/Documents'));

Replace the ``@ORM\Document`` annotations with the ``#[ORM\Document]`` attribute.

.. code-block:: diff

    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

    - /**
    -  * @ODM\Document
    -  */
    + #[ORM\Document]
    class User
    {
    -    /**
    -     * @ORM\Id
    -     */
    +     #[ORM\Id]
        public string $id;

    -    /**
    -     * @ORM\Column(type="string")
    -     */
    +     #[ORM\Column(type: "string")]
        public string $name;
    }

.. note::

   You can use Rector to automate the migration process. See
   `How to Upgrade Annotations to Attributes`_ for more information.

.. _How to Upgrade Annotations to Attributes: https://getrector.com/blog/how-to-upgrade-annotations-to-attributes
