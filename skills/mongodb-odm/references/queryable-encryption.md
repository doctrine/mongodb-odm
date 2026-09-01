# Queryable Encryption

**Requires MongoDB Enterprise 7.0+ or Atlas** — not Community Edition.

## 1. Marking fields as encrypted

```php
<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\EncryptQuery;

#[ODM\Document]
class Patient
{
    #[ODM\Id]
    public string $id;

    #[ODM\EmbedOne(targetDocument: PatientRecord::class)]
    public PatientRecord $patientRecord;
}

#[ODM\EmbeddedDocument]
class PatientRecord
{
    // Encrypted, equality-queryable — find a patient by exact SSN.
    #[ODM\Field(type: 'string')]
    #[ODM\Encrypt(queryType: EncryptQuery::Equality)]
    public string $ssn;

    // Encrypted as a whole object; no queryType means non-queryable.
    #[ODM\EmbedOne(targetDocument: Billing::class)]
    #[ODM\Encrypt]
    public Billing $billing;

    // Encrypted, range-queryable.
    #[ODM\Field(type: 'int')]
    #[ODM\Encrypt(queryType: EncryptQuery::Range, min: 0, max: 5000, sparsity: 1)]
    public int $billingAmount;
}
```

`#[Encrypt]` with no `queryType` encrypts but makes the field **not queryable at all**. `EncryptQuery::Equality` — exact match. `EncryptQuery::Range` — takes `min`/`max`/`sparsity`. No other query types are currently supported.

## 2. Configuring the encrypted client / key vault / KMS

```php
<?php

use Doctrine\ODM\MongoDB\Configuration;
use MongoDB\BSON\Binary;

$keyFile = __DIR__ . '/master-key.bin';
if (!file_exists($keyFile)) {
    file_put_contents($keyFile, random_bytes(96)); // 'local' KMS is dev-only; use AWS/Azure/GCP/KMIP in production
}

$config = new Configuration();
$config->setAutoEncryption(['keyVaultNamespace' => 'encryption.datakeys']);
$config->setKmsProvider(['type' => 'local', 'key' => new Binary(file_get_contents($keyFile), Binary::TYPE_GENERIC)]);
// ...other Configuration setup (hydrator dir, metadata driver, default DB)...
```

Each `#[Encrypt]` field gets a Data Encryption Key (DEK), generated and stored in the key vault (`<database>.datakeys` by default, or `keyVaultNamespace`).

```php
<?php

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Client;

$client = new Client(uri: 'mongodb://localhost:27017/', driverOptions: $config->getDriverOptions());
$dm = DocumentManager::create($client, $config);
```

Encrypted collections need explicit creation up front — not implicit on first insert:

```php
$schemaManager = $dm->getSchemaManager();
$schemaManager->dropDocumentCollection(Patient::class);
$schemaManager->createDocumentCollection(Patient::class);
```

Then persist/query normally — encryption and decryption happen transparently, including `findOneBy(['patientRecord.ssn' => '123-456-7890'])`.

## 3. On-disk representation

`#[Encrypt]` fields store as BSON **binary, subtype 6**, not their original type; the driver adds a `__safeContent__` array field internally to support encrypted equality/range search.

## 4. Gotchas

1. **`Configuration::setKmsProvider()` supports only one KMS provider.** Multiple providers require manually building `kmsProviders` and passing it as a driver option, bypassing the ODM helper.
2. **Incompatible with `SINGLE_COLLECTION` inheritance** (see `mapping.md`) — the `SchemaManager` can't merge encrypted-field maps across classes sharing a collection.
3. **No partial updates on encrypted embedded documents/collections** — only `set`/`atomicSet` collection strategies work with them.
4. **Not every server-side QE limitation is ODM-specific** — see MongoDB's Queryable Encryption limitations documentation (https://www.mongodb.com/docs/manual/core/queryable-encryption/reference/limitations/) for constraints that apply regardless of PHP/ODM.
5. **Treat an encrypted field's `queryType` as fixed once data exists** — changing it later typically requires re-encrypting and migrating the collection, so plan the query type up front.
