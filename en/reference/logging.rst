Logging
=======

If you want to turn on logging and receive information about
queries made to the database you can do so on your
``Doctrine\ODM\MongoDB\Configuration`` instance:

.. code-block:: php

    <?php

    // ...
    
    $config->setLoggerCallable(function(array $log) {
        print_r($log);
    });

You can register any PHP callable and it will be notified with a
single argument that is an array of information about the query
being sent to the database.

Just like the anonymous function above, you could pass an array
with a object instance and a method to call:

.. code-block:: php

    <?php

    // ...
    
    $config->setLoggerCallable(array($obj, 'method'));