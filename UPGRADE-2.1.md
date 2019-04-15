# UPGRADE FROM 2.0 to 2.1

The `Doctrine\ODM\MongoDB\Id\AbstractIdGenerator` class has been deprecated. Custom ID generators must implement
the `Doctrine\ODM\MongoDB\Id\IdGenerator` interface.

The `Doctrine\ODM\MongoDB\Mapping\ClassMetadata` class has been marked final. The class will no longer be extendable 
in ODM 3.0.
