# UPGRADE FROM 1.x TO 1.2

## Mapping changes

### Immutable documents

Documents can be marked as immutable by setting the `readOnly` option in the
document mapping:

```php
/**
 * @ODM\Document(readOnly=true)
 */
class ReadOnlyDocument
{
    // ...
}
```
```xml
<?xml version="1.0" encoding="UTF-8"?>

<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                  http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

    <document name="ReadOnlyDocument" read-only="true">
        <!-- ... -->
    </document>
</doctrine-mongo-mapping>
```
```yml
ReadOnlyDocument:
  type: document
  readOnly: true
```

Immutable documents will not be updated in the database after they've been
persisted. You can still create a query builder and manually update data at your
own risk.

### Read preference

In addition to specifying a read preference for a query, you can now specify a
default read preference for a document using the `readPreference` option:

```php
/**
 * @Document
 * @ODM\ReadPreference("primaryPreferred", tags={
 *   { "dc"="east" },
 *   { "dc"="west" },
 *   {  },
 * })
 */
class ReadPreferenceDocument
{
    // ...
}
```
```xml
<?xml version="1.0" encoding="UTF-8"?>

<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                  http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

    <document name="ReadPreferenceDocument">
        <read-preference mode="primaryPreferred">
            <tag-set>
                <tag name="dc" value="east"/>
            </tag-set>
            <tag-set>
                <tag name="dc" value="west"/>
            </tag-set>
            <tag-set />
        </read-preference>

        <!-- ... -->
    </document>
</doctrine-mongo-mapping>
```
```yml
ReadPreferenceDocument:
  type: document
  readPreference:
    mode: primaryPreferred
    tagSets:
      - { dc: east }
      - { dc: west }
      - {  }
```

Note that specifying a `readPreference` and `slaveOkay` at the same time will
cause an exception.

To replace an existing `slaveOkay` mapping, you can apply a `readPreference` of
`secondaryPreferred`. 

### Reference mapping

When mapping references, ODM previously previously offered three options to 
store a reference via the `storeAs` option:

 * `storeAs="id"` (previously known as `simple` reference): this reference type
   simply stores the identifier of the referenced document in the owning
   document.
 * `storeAs="dbRef"`: this reference type creates a DBRef object, but doesn't
   include a value for the `$db` field
 * `storeAs="dbRefWithDb"`: similar to `dbRef`, but also writes the `$db` field

Since `id` can't be used with discriminated references and the two `dbRef` types
may be incompatible with aggregation pipelines, there is a new reference type
`ref`. This reference type is similar to `dbRef` as it stores an object, but it 
only stores the identifier of the reference as `id` (without a leading dollar 
sign). This makes it usable with discriminated references (which are stored 
inside the reference object) as well as aggregation pipelines. While not 
officially deprecated, using the `dbRef` reference types is strongly limited 
because of its [limited support in aggregation pipeline queries](https://jira.mongodb.org/browse/SERVER-14466).

### Priming inverse references

When specifying an inverse reference via `mappedBy` or `repositoryMethod`, you
can now specify primers that will be run on mapped documents automatically:

```php
/** @Document */
class Blog
{
    /** @ReferenceMany(targetDocument=Post::class, prime={"author"}) */
    private $posts;
}
```
```xml
<?xml version="1.0" encoding="UTF-8"?>

<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                  http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

    <document name="Blog">
        <reference-many target-document="Post" field="posts">
            <prime>
                <field name="author" />
            </prime>
        </reference-many>
    </document>
</doctrine-mongo-mapping>
```
```yml
Blog:
  type: document
  referenceMany:
    posts:
      targetDocument: Post
      prime:
        - author
```

### Query result documents

Query result documents are similar to regular documents, except that they won't
be written to the database. Query result documents are used to return objects
from aggregation pipeline queries. You can map a query result document similar
to a regular document:

```php
/** @QueryResultDocument */
class UserPurchases
{
    /** @ReferenceOne(targetDocument="User", name="_id") */
    private $user;

    /** @Field(type="int") */
    private $numPurchases;

    /** @Field(type="float") */
    private $amount;
}
```
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                  http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
    <query-result-document name="UserPurchases">
        <field fieldName="numPurchases" type="int" />
        <field fieldName="amount" type="float" />
        <reference-one field="user" target-document="Documents\User" name="_id" />
    </query-result-document>
</doctrine-mongo-mapping>
```
```yml
UserPurchases:
  type: queryResultDocument
  fields:
    user:
      name: _id
      targetDocument: Documents\User
    numPurchases:
      type: int
    amount:
      type: float
```

Attempting to persist a query result document will result in an exception.

Query result documents can be used to hydrate results from an aggregation
pipeline query:
```php
$purchaes = $documentManager->getRepository(Purchases::class)->createAggregationBuilder()
    ->hydrate(UserPurchases::class)
    ->group()
        ->field('id')
        ->expression('$user')
        ->field('numPurchases')
        ->sum(1)
        ->field('amount')
        ->sum('$amount');
;        
```

### Validate mappings

The new `odm:schema:validate` command allows you to validate your schema to
ensure it is cached and read properly. Errors in mapping can sometimes only
manifest themselves after the mapping has been loaded from the cache. This 
command gives you a chance to catch these errors.

### Custom starting value for increment generators

When using an increment ID generator for a document, you can now specify a
custom starting ID that will be used if no record of the sequence is present:

```php
/** @ODM\Document */
class DocumentWithCustomStartingId
{
    /** @ODM\Id(strategy="increment", options={"startingId"=10}) */
    public $id;
}
```
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                  http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
    <document name="DocumentWithCustomStartingId">
        <field name="id" id="true" strategy="increment">
            <id-generator-option name="startingId" value="10" />
        </field>
    </document>
</doctrine-mongo-mapping>
```
```yml
DocumentWithCustomStartingId:
  type: document
  fields:
    id:
      fieldName: id
      id: true
      strategy: increment
      options:
        startingId: 10
```

### Deprecations

 * Calling `DocumentManager::flush` from within a lifecycle event subscriber or
   lifecycle method is deprecated and will throw an exception in ODM 2.0. You
   should not attempt nested flushes as ODM can't guarantee all objects will be
   properly written to the database.
 * The `slaveOkay` option has been deprecated and will be removed in ODM 2.0.
   You should replace instances of `slaveOkay=true` with a read preference of
   `secondaryPreferred`.
 * The `requireIndex` option in the document mapping has been deprecated and
   will be removed in ODM 2.0. If you want to require indexes for your queries,
   take a look at the [`notablescan`](https://docs.mongodb.com/manual/reference/parameters/#param.notablescan)
   option in the MongoDB server.
 * The `name` and `fieldName` properties in the `@DiscriminatorField` annotation
   have been deprecated and will be removed in ODM 2.0. Use the annotation
   without specifying a property instead: `@DiscriminatorField("field")`
 * Loading annotations through the `DoctrineAnnotations.php` file has been
   deprecated. The file will be removed in ODM 2.0.
 * The `Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver::registerAnnotationClasses`
   method has been deprecated and will be removed in ODM 2.0. Use the
   `AnnotationRegistry::registerLoader` method to register an autoloader.
 * The following mapping annotations have been deprecated and will be removed in
   ODM 2.0:
    - `@Bin`
    - `@BinCustom`
    - `@BinFunc`
    - `@BinMD5`
    - `@BinUUID`
    - `@BinUUIDRFC4222`
    - `@Bool`
    - `@Boolean`
    - `@Collection`
    - `@Date`
    - `@Float`
    - `@Hash`
    - `@Increment`
    - `@Int`
    - `@Integer`
    - `@Key`
    - `@ObjectId`
    - `@Raw`
    - `@String`
    - `@Timestamp`

   To map fields, use the `@Field` annotation with the appropriate `type`
   instead.


## Repositories

### Final default repository factory

The `Doctrine\ODM\MongoDB\Repository\DefaultRepositoryFactory` class has been
marked final. The class will no longer be extendable in ODM 2.0.

### Magic find methods

The magic find methods `findBy<Field>` and `findOneBy<Field>` have been
deprecated and will be removed in ODM 2.0. You should use the `findBy` and
`findOneBy` methods or specify explicit methods to find the data you're looking
for:

Before:
```php
$documentManager->getRepository(Order::class)->findByStatus('pending');
$documentManager->getRepository(Order::class)->findOneByEmail('user@example.com');
```
After:
```php
$documentManager->getRepository(Order::class)->findBy(['status' => pending']);
$documentManager->getRepository(Order::class)->findOneBy(['email' => 'user@example.com']);
```

As an alternative:
```php
class MyRepository extends DocumentRepository
{
    /**
     * @return Order[]
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findOneByEmail(string $email): ?Order
    {
        return $this->findOneBy(['email' => $email);
    }
}
```

### Abstract repository factory

ODM 1.2 introduces an `AbstractRepositoryFactory` that you can extend if you're
building your own repository factory. This class takes care of tracking
repository instances and only requires that you implement the
`instantiateRepository` method which is called when a new repository instance is
required.

## Query builder

### Updating multiple documents

The `Builder::update` and `Builder::multiple` methods have been deprecated and
will be removed in ODM 2.0. Use the `updateOne` and `updateMany` methods
accordingly.

Before:
```php
$builder
    ->update()
    ->field('enabled')
    ->set(true);

$builder
    ->update()
    ->multiple()
    ->field('enabled')
    ->set(true);
```
After:
```php
$builder
    ->updateOne()
    ->field('enabled')
    ->set(true);

$builder
    ->updateMany()
    ->field('enabled')
    ->set(true);
```

## Group helper

The `group` method in the query builder has been deprecated and will be removed
in ODM 2.0. The `group` command has been deprecated in MongoDB and is superseded
by the `$group` aggregation pipeline stage and the `mapReduce` command.

## Aggregation builder

ODM 1.2 adds an aggregation builder which can be used to create aggregation
pipeline queries with an API similar to the query builder.

## Deprecations

 * The `Doctrine\ODM\MongoDB\EagerCursor` class has been deprecated and will be
   removed in ODM 2.0. Use the `Doctrine\ODM\MongoDB\Cursor` class instead.
 * The `Doctrine\ODM\MongoDB\MongoDBException::queryNotIndexed` method has been
   deprecated and will be removed in ODM 2.0.
 * The `Doctrine\ODM\MongoDB\Query\FieldExtractor` class has been deprecated and
   will be removed in ODM 2.0.
 * The `getFieldsInQuery`, `isIndexed` and `getUnindexedFields2` methods in
   `Doctrine\ODM\MongoDB\Query\Query` have been deprecated and will be removed
   in ODM 2.0.
 * The `createDatabases` and `createDocumentDatabase` methods in
   `Doctrine\ODM\MongoDB\SchemaManager` have been deprecated and will be removed
   in ODM 2.0. Databases are created automatically by MongoDB >= 3.0.
 * The `--db` option in `odm:schema:create` has been deprecated and will be
   removed in ODM 2.0.
