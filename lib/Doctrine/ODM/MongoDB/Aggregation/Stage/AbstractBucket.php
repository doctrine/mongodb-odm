<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Types\Type;

use function array_map;
use function is_array;
use function is_string;
use function substr;

/**
 * Abstract class with common functionality for $bucket and $bucketAuto stages
 *
 * @internal
 */
abstract class AbstractBucket extends Stage
{
    /** @var Bucket\AbstractOutput|null */
    protected $output;

    /** @var Expr|array<string, mixed>|string */
    protected $groupBy;

    public function __construct(Builder $builder, private DocumentManager $dm, private ClassMetadata $class)
    {
        parent::__construct($builder);
    }

    /**
     * An expression to group documents by. To specify a field path, prefix the
     * field name with a dollar sign $ and enclose it in quotes.
     *
     * @param array<string, mixed>|Expr|string $expression
     */
    public function groupBy($expression): static
    {
        $this->groupBy = $expression;

        return $this;
    }

    public function getExpression(): array
    {
        $stage = [
            $this->getStageName() => [
                'groupBy' => $this->convertExpression($this->groupBy),
            ] + $this->getExtraPipelineFields(),
        ];

        if ($this->output !== null) {
            $stage[$this->getStageName()]['output'] = $this->output->getExpression();
        }

        return $stage;
    }

    /** @return mixed[] */
    abstract protected function getExtraPipelineFields(): array;

    /**
     * Returns the stage name with the dollar prefix
     */
    abstract protected function getStageName(): string;

    /**
     * @param array|mixed|string $expression
     *
     * @return array|mixed|string
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

    private function getDocumentPersister(): DocumentPersister
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }
}
