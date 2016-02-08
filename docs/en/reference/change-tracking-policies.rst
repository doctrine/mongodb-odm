.. _change_tracking_policies:

Change Tracking Policies
========================

Change tracking is the process of determining what has changed in
managed documents since the last time they were synchronized with
the database.

Doctrine provides 3 different change tracking policies, each having
its particular advantages and disadvantages. The change tracking
policy can be defined on a per-class basis (or more precisely,
per-hierarchy).

Deferred Implicit
~~~~~~~~~~~~~~~~~

The deferred implicit policy is the default change tracking policy
and the most convenient one. With this policy, Doctrine detects the
changes by a property-by-property comparison at commit time and
also detects changes to documents or new documents that are
referenced by other managed documents. Although the most convenient policy,
it can have negative effects on performance if you are dealing with large units
of work. Since Doctrine can't know what has changed, it needs to check
all managed documents for changes every time you invoke DocumentManager#flush(),
making this operation rather costly.

Deferred Explicit
~~~~~~~~~~~~~~~~~

The deferred explicit policy is similar to the deferred implicit
policy in that it detects changes through a property-by-property
comparison at commit time. The difference is that only documents are
considered that have been explicitly marked for change detection
through a call to DocumentManager#persist(document) or through a save
cascade. All other documents are skipped. This policy therefore
gives improved performance for larger units of work while
sacrificing the behavior of "automatic dirty checking".

Therefore, flush() operations are potentially cheaper with this
policy. The negative aspect this has is that if you have a rather
large application and you pass your objects through several layers
for processing purposes and business tasks you may need to track
yourself which documents have changed on the way so you can pass
them to DocumentManager#persist().

This policy can be configured as follows:

.. code-block:: php

    <?php

    /**
     * @Document
     * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
     */
    class User
    {
        // ...
    }

Notify
~~~~~~

This policy is based on the assumption that the documents notify
interested listeners of changes to their properties. For that
purpose, a class that wants to use this policy needs to implement
the ``NotifyPropertyChanged`` interface from the Doctrine
namespace. As a guideline, such an implementation can look as
follows:

.. code-block:: php

    <?php

    use Doctrine\Common\NotifyPropertyChanged,
        Doctrine\Common\PropertyChangedListener;
    
    /**
     * @Document
     * @ChangeTrackingPolicy("NOTIFY")
     */
    class MyDocument implements NotifyPropertyChanged
    {
        // ...
    
        private $_listeners = array();
    
        public function addPropertyChangedListener(PropertyChangedListener $listener)
        {
            $this->_listeners[] = $listener;
        }
    }

Then, in each property setter of this class or derived classes, you
need to notify all the ``PropertyChangedListener`` instances. As an
example we add a convenience method on ``MyDocument`` that shows this
behavior:

.. code-block:: php

    <?php

    // ...
    
    class MyDocument implements NotifyPropertyChanged
    {
        // ...
    
        protected function _onPropertyChanged($propName, $oldValue, $newValue)
        {
            if ($this->_listeners) {
                foreach ($this->_listeners as $listener) {
                    $listener->propertyChanged($this, $propName, $oldValue, $newValue);
                }
            }
        }
    
        public function setData($data)
        {
            if ($data != $this->data) {
                $this->_onPropertyChanged('data', $this->data, $data);
                $this->data = $data;
            }
        }
    }

You have to invoke ``_onPropertyChanged`` inside every method that
changes the persistent state of ``MyDocument``.

The check whether the new value is different from the old one is
not mandatory but recommended. That way you also have full control
over when you consider a property changed.

The negative point of this policy is obvious: You need implement an
interface and write some plumbing code. But also note that we tried
hard to keep this notification functionality abstract. Strictly
speaking, it has nothing to do with the persistence layer. You may
find that property notification events come in handy in many other
scenarios as well. As mentioned earlier, the ``Doctrine\Common``
namespace is not that evil and consists solely of very small classes
and interfaces that have almost no external dependencies and that you can easily take with you should
you want to swap out the persistence layer. This change tracking policy
does not introduce a dependency on the Doctrine persistence
layer.

The positive point and main advantage of this policy is its
effectiveness. It has the best performance characteristics of the 3
policies with larger units of work and a flush() operation is very
cheap when nothing has changed.
