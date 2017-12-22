<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\Common\Persistence\Mapping\MappingException as BaseMappingException;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

class Out extends Stage
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $collection;

    /**
     * @param Builder $builder
     * @param string $collection
     * @param DocumentManager $documentManager
     */
    public function __construct(Builder $builder, $collection, DocumentManager $documentManager)
    {
        parent::__construct($builder);

        $this->dm = $documentManager;
        $this->out($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$out' => $this->collection
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function out($collection)
    {
        try {
            $class = $this->dm->getClassMetadata($collection);
        } catch (BaseMappingException $e) {
            $this->collection = (string) $collection;
            return $this;
        }

        $this->fromDocument($class);
        return $this;
    }

    private function fromDocument(ClassMetadata $classMetadata)
    {
        if ($classMetadata->isSharded()) {
            throw MappingException::cannotUseShardedCollectionInOutStage($classMetadata->name);
        }

        $this->collection = $classMetadata->getCollection();
    }
}
