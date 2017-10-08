<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\MongoDB\Aggregation\Builder;
use Doctrine\MongoDB\Aggregation\Stage as BaseStage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class SortByCount extends BaseStage\SortByCount
{
    public function __construct(Builder $builder, $fieldName, DocumentManager $documentManager, ClassMetadata $class)
    {
        $documentPersister = $documentManager->getUnitOfWork()->getDocumentPersister($class->name);

        parent::__construct($builder, '$' . $documentPersister->prepareFieldName(substr($fieldName, 1)));
    }
}
