<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\MongoDB\Aggregation\Builder;
use \Doctrine\MongoDB\Aggregation\Stage as BaseStage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;

class ReplaceRoot extends BaseStage\ReplaceRoot
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ClassMetadata
     */
    private $class;

    public function __construct(Builder $builder, DocumentManager $documentManager, ClassMetadata $class, $expression = null)
    {
        $this->dm = $documentManager;
        $this->class = $class;

        parent::__construct($builder, $expression);
    }

    protected function convertExpression($expression)
    {
        if (is_array($expression)) {
            return array_map([$this, 'convertExpression'], $expression);
        } elseif (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        } else {
            return Type::convertPHPToDatabaseValue(parent::convertExpression($expression));
        }
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister()
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }
}
