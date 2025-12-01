# UPGRADE FROM 2.15 to 2.16

## Lazy Proxy Directory

Using proxy classes with PHP 8.4+ is deprecated, only native lazy objects will
be supported in MongoDB ODM 3.0.

Calling `Doctrine\ODM\MongoDB\Configuration::setProxyDir()` or
`Doctrine\ODM\MongoDB\Configuration::getProxyDir()` is deprecated and triggers
a deprecation notice when using native lazy objects.
