<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;

class ReplaceRoot extends Operator
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ClassMetadata
     */
    private $class;

    /**
     * @var string|null
     */
    private $expression;

    public function __construct(Builder $builder, DocumentManager $documentManager, ClassMetadata $class, $expression = null)
    {
        parent::__construct($builder);

        $this->dm = $documentManager;
        $this->class = $class;
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$replaceRoot' => $this->expression !== null ? $this->convertExpression($this->expression) : $this->expr->getExpression()
        ];
    }

    private function convertExpression($expression)
    {
        if (is_array($expression)) {
            return array_map([$this, 'convertExpression'], $expression);
        } elseif (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        } else {
            return Type::convertPHPToDatabaseValue(Expr::convertExpression($expression));
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
