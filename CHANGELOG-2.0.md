CHANGELOG for 2.0.x
===================

Welcome to ODMng!

Upgrade Path
------------

#### Deprecated code has been removed

Please review list of deprecated functionality we included with previous releases and adhere
to made suggestions:

* [1.1 release](https://github.com/doctrine/mongodb-odm/blob/master/CHANGELOG-1.1.md#deprecations)
* [1.2 release](https://github.com/doctrine/mongodb-odm/blob/master/CHANGELOG-1.2.md#deprecations)

#### `AnnotationDriver::registerAnnotationClasses()` has been removed

`registerAnnotationClasses()` method was registering ODM annotations in the `AnnotationRegistry`
and was recommended to be called during bootstrap of your application. The new way to ensure
annotations can be autoloaded properly is registering Composer's autoloader instead:

```php
use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require_once('path/to/vendor/autoload.php');

AnnotationRegistry::registerLoader([$loader, 'loadClass']);
```
