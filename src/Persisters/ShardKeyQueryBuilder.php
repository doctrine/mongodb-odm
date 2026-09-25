<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Query\CriteriaPreparer;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;

use function array_keys;
use function array_merge;
use function assert;
use function is_string;

/**
 * Builds the shard-key-aware query used to locate a single document, shared
 * by DocumentPersister's write methods and DocumentLoader::refresh().
 *
 * @internal
 *
 * @template T of object = object
 */
final class ShardKeyQueryBuilder
{
    /** @phpstan-param ClassMetadata<T> $class */
    public function __construct(
        private DocumentManager $dm,
        private UnitOfWork $uow,
        private ClassMetadata $class,
        private CriteriaPreparer $criteriaPreparer,
    ) {
    }

    /**
     * Get shard key aware query for single document.
     *
     * @return array<string, mixed>
     */
    public function getQueryForDocument(object $document): array
    {
        $id = $this->dm->getDocumentRegistry()->getDocumentIdentifier($document);
        $id = $this->class->getDatabaseIdentifierValue($id);

        $shardKeyQueryPart = $this->getShardKeyQuery($document);

        return array_merge(['_id' => $id], $shardKeyQueryPart);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MongoDBException
     */
    private function getShardKeyQuery(object $document): array
    {
        if (! $this->class->isSharded()) {
            return [];
        }

        $shardKey = $this->class->getShardKey();
        $keys     = array_keys($shardKey['keys']);
        $data     = $this->uow->getDocumentActualData($document);

        $shardKeyQueryPart = [];
        foreach ($keys as $key) {
            assert(is_string($key));
            $mapping = $this->class->getFieldMappingByDbFieldName($key);
            $this->guardMissingShardKey($document, $key, $data);

            if (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                $reference = $this->criteriaPreparer->prepareReference(
                    $key,
                    $data[$mapping['fieldName']],
                    $mapping,
                    false,
                );
                foreach ($reference as $keyValue) {
                    $shardKeyQueryPart[$keyValue[0]] = $keyValue[1];
                }
            } else {
                $value                   = Type::getType($mapping['type'])->convertToDatabaseValue($data[$mapping['fieldName']]);
                $shardKeyQueryPart[$key] = $value;
            }
        }

        return $shardKeyQueryPart;
    }

    /**
     * If the document is new, ignore shard key field value, otherwise throw an
     * exception. Also, shard key field should be present in actual document
     * data.
     *
     * @param array<string, mixed> $actualDocumentData
     *
     * @throws MongoDBException
     */
    private function guardMissingShardKey(object $document, string $shardKeyField, array $actualDocumentData): void
    {
        $dcs      = $this->uow->getDocumentChangeSet($document);
        $isUpdate = $this->uow->isScheduledForUpdate($document);

        $fieldMapping = $this->class->getFieldMappingByDbFieldName($shardKeyField);
        $fieldName    = $fieldMapping['fieldName'];

        if ($isUpdate && isset($dcs[$fieldName]) && $dcs[$fieldName][0] !== $dcs[$fieldName][1]) {
            throw MongoDBException::shardKeyFieldCannotBeChanged($shardKeyField, $this->class->getName());
        }

        if (! isset($actualDocumentData[$fieldName])) {
            throw MongoDBException::shardKeyFieldMissing($shardKeyField, $this->class->getName());
        }
    }
}
