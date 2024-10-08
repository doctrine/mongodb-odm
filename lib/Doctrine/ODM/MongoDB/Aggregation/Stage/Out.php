<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\Mapping\MappingException as BaseMappingException;

use function is_array;

/**
 * @phpstan-import-type OutputCollection from Merge
 * @phpstan-type OutStageExpression array{'$out': OutputCollection}
 */
class Out extends Stage
{
    /** @phpstan-var OutputCollection */
    private array|string $out;

    public function __construct(Builder $builder, string $collection, private DocumentManager $dm)
    {
        parent::__construct($builder);

        $this->out($collection);
    }

    public function getExpression(): array
    {
        return [
            '$out' => $this->out,
        ];
    }

    /**
     * @param string|array $collection
     * @phpstan-param OutputCollection $collection
     */
    public function out($collection): Stage\Out
    {
        if (is_array($collection)) {
            $this->out = $collection;

            return $this;
        }

        try {
            $class = $this->dm->getClassMetadata($collection);
        } catch (BaseMappingException) {
            $this->out = $collection;

            return $this;
        }

        $this->fromDocument($class);

        return $this;
    }

    private function fromDocument(ClassMetadata $classMetadata): void
    {
        if ($classMetadata->isSharded()) {
            throw MappingException::cannotUseShardedCollectionInOutStage($classMetadata->name);
        }

        $this->out = $classMetadata->getCollection();
    }
}
