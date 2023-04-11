<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Types\Type;

use function array_map;
use function is_array;
use function is_string;
use function substr;

abstract class AbstractReplace extends Operator
{
    /** @param string|mixed[]|Expr|null $expression */
    final public function __construct(Builder $builder, protected DocumentManager $dm, protected ClassMetadata $class, protected $expression = null)
    {
        Operator::__construct($builder);
    }

    private function getDocumentPersister(): DocumentPersister
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }

    /** @return array<string, mixed>|string */
    protected function getReplaceExpression()
    {
        return $this->expression !== null ? $this->convertExpression($this->expression) : $this->expr->getExpression();
    }

    /**
     * @param mixed[]|string|mixed $expression
     *
     * @return mixed[]|string|mixed
     */
    private function convertExpression($expression)
    {
        if (is_array($expression)) {
            return array_map([$this, 'convertExpression'], $expression);
        }

        if (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        }

        return Type::convertPHPToDatabaseValue(Expr::convertExpression($expression));
    }
}
