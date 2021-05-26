Validation of Documents
=======================

Validation of Documents - Application Side
------------------------------------------

.. sectionauthor:: Benjamin Eberlei <kontakt@beberlei.de>

Doctrine does not ship with any internal validators, the reason
being that we think all the frameworks out there already ship with
quite decent ones that can be integrated into your Domain easily.
What we offer are hooks to execute any kind of validation.

.. note::

    You don't need to validate your documents in the lifecycle
    events. Its only one of many options. Of course you can also
    perform validations in value setters or any other method of your
    documents that are used in your code.

Documents can register lifecycle event methods with Doctrine that
are called on different occasions. For validation we would need to
hook into the events called before persisting and updating. Even
though we don't support validation out of the box, the
implementation is even simpler than in Doctrine 1 and you will get
the additional benefit of being able to re-use your validation in
any other part of your domain.

Say we have an ``Order`` with several ``OrderLine`` instances. We
never want to allow any customer to order for a larger sum than he
is allowed to:

.. code-block:: php

    <?php

    class Order
    {
        public function assertCustomerAllowedBuying(): void
        {
            $orderLimit = $this->customer->getOrderLimit();

            $amount = 0;
            foreach ($this->orderLines AS $line) {
                $amount += $line->getAmount();
            }

            if ($amount > $orderLimit) {
                throw new CustomerOrderLimitExceededException();
            }
        }
    }

Now this is some pretty important piece of business logic in your
code, enforcing it at any time is important so that customers with
a unknown reputation don't owe your business too much money.

We can enforce this constraint in any of the metadata drivers.
First Annotations:

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Document @HasLifecycleCallbacks */
        class Order
        {
            /** @PrePersist @PreUpdate */
            public function assertCustomerAllowedBuying(): void {}
        }

    .. code-block:: xml

        <doctrine-mapping>
            <document name="Order">
                <lifecycle-callbacks>
                    <lifecycle-callback type="prePersist" method="assertCustomerallowedBuying" />
                    <lifecycle-callback type="preUpdate" method="assertCustomerallowedBuying" />
                </lifecycle-callbacks>
            </document>
        </doctrine-mapping>

Now validation is performed whenever you call
``DocumentManager#persist($order)`` or when you call
``DocumentManager#flush()`` and an order is about to be updated. Any
Exception that happens in the lifecycle callbacks will be cached by
the DocumentManager and the current transaction is rolled back.

Of course you can do any type of primitive checks, not null,
email-validation, string size, integer and date ranges in your
validation callbacks.

.. code-block:: php

    <?php

    /** @Document @HasLifecycleCallbacks */
    class Order
    {
        /** @PrePersist @PreUpdate */
        public function validate(): void
        {
            if (!($this->plannedShipDate instanceof DateTime)) {
                throw new ValidateException();
            }

            if ($this->plannedShipDate->format('U') < time()) {
                throw new ValidateException();
            }

            if ($this->customer === null) {
                throw new OrderRequiresCustomerException();
            }
        }
    }

What is nice about lifecycle events is, you can also re-use the
methods at other places in your domain, for example in combination
with your form library. Additionally there is no limitation in the
number of methods you register on one particular event, i.e. you
can register multiple methods for validation in "PrePersist" or
"PreUpdate" or mix and share them in any combinations between those
two events.

There is no limit to what you can and can't validate in
"PrePersist" and "PreUpdate" as long as you don't create new document
instances. This was already discussed in the previous blog post on
the Versionable extension, which requires another type of event
called "onFlush".

Further readings: :doc:`Lifecycle Events <../reference/events>`

Validation of Documents - Database Side
---------------------------------------

.. sectionauthor:: Alexandre Abrioux <alexandre-abrioux@users.noreply.github.com>

.. note::

    This feature has been introduced in version 2.3.0

MongoDB ≥ 3.6 offers the capability to validate documents during
insertions and updates through a schema associated to the collection
(cf. `MongoDB documentation <https://docs.mongodb.com/manual/core/schema-validation/>`_).

Doctrine MongoDB ODM now provides a way to take advantage of this functionality
thanks to the new :doc:`@Validation <../reference/annotations-reference#validation>`
annotation and its properties (also available with XML mapping):

-
  ``validator`` - The schema that will be used to validate documents.
  It is a string representing a BSON document under the
  `Extended JSON specification <https://github.com/mongodb/specifications/blob/master/source/extended-json.rst>`_.
-
  ``action`` - The behavior followed by MongoDB to handle documents that
  violate the validation rules.
-
  ``level`` - The threshold used by MongoDB to filter operations that
  will get validated.

Once defined, those options will be added to the collection after running
the ``odm:schema:create`` or ``odm:schema:update`` command.

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
        use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

        /**
         * @ODM\Document
         * @ODM\Validation(
         *     validator=SchemaValidated::VALIDATOR,
         *     action=ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
         *     level=ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
         * )
         */
        class SchemaValidated
        {
            public const VALIDATOR = <<<'EOT'
        {
            "$jsonSchema": {
                "required": ["name"],
                "properties": {
                    "name": {
                        "bsonType": "string",
                        "description": "must be a string and is required"
                    }
                }
            },
            "$or": [
                { "phone": { "$type": "string" } },
                { "email": { "$regex": { "$regularExpression" : { "pattern": "@mongodb\\.com$", "options": "" } } } },
                { "status": { "$in": [ "Unknown", "Incomplete" ] } }
            ]
        }
        EOT;

            /** @ODM\Id */
            private $id;

            /** @ODM\Field(type="string") */
            private $name;

            /** @ODM\Field(type="string") */
            private $phone;

            /** @ODM\Field(type="string") */
            private $email;

            /** @ODM\Field(type="string") */
            private $status;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                          xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                          http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

            <document name="SchemaValidated">
                <schema-validation action="warn" level="moderate">
                    {
                        "$jsonSchema": {
                            "required": ["name"],
                            "properties": {
                                "name": {
                                    "bsonType": "string",
                                    "description": "must be a string and is required"
                                }
                            }
                        },
                        "$or": [
                            { "phone": { "$type": "string" } },
                            { "email": { "$regex": { "$regularExpression" : { "pattern": "@mongodb\\.com$", "options": "" } } } },
                            { "status": { "$in": [ "Unknown", "Incomplete" ] } }
                        ]
                    }
                </schema-validation>
            </document>
        </doctrine-mongo-mapping>

Please refer to the :doc:`@Validation <../reference/annotations-reference#document>` annotation reference
for more details on how to use this feature.
