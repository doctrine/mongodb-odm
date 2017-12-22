<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * Abstract class with common functionality for $bucket and $bucketAuto stages
 *
 * @internal
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.5
 */
abstract class AbstractBucket extends Stage
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
     * @var Bucket\BucketOutput|null
     */
    protected $output;

    /**
     * @var Expr
     */
    protected $groupBy;

    public function __construct(Builder $builder, DocumentManager $documentManager, ClassMetadata $class)
    {
        $this->dm = $documentManager;
        $this->class = $class;

        parent::__construct($builder);
    }

    /**
     * An expression to group documents by. To specify a field path, prefix the
     * field name with a dollar sign $ and enclose it in quotes.
     *
     * @param array|Expr $expression
     * @return $this
     */
    public function groupBy($expression)
    {
        $this->groupBy = $expression;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
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

    /**
     * @return array
     */
    abstract protected function getExtraPipelineFields();

    /**
     * Returns the stage name with the dollar prefix
     *
     * @return string
     */
    abstract protected function getStageName();

    private function convertExpression($expression)
    {
        if (is_array($expression)) {
            return array_map([$this, 'convertExpression'], $expression);
        } elseif (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        }

        return Type::convertPHPToDatabaseValue(Expr::convertExpression($expression));
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister()
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }
}
