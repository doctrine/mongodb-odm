<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\Mapping\MappingException as BaseMappingException;

class Out extends Stage
{
    /** @var DocumentManager */
    private $dm;

    /** @var string */
    private $collection;

    public function __construct(Builder $builder, string $collection, DocumentManager $documentManager)
    {
        parent::__construct($builder);

        $this->dm = $documentManager;
        $this->out($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression(): array
    {
        return [
            '$out' => $this->collection,
        ];
    }

    public function out(string $collection): Stage\Out
    {
        try {
            $class = $this->dm->getClassMetadata($collection);
        } catch (BaseMappingException $e) {
            $this->collection = $collection;

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

        $this->collection = $classMetadata->getCollection();
    }
}
