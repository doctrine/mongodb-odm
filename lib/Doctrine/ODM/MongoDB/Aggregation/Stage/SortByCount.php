<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

use function substr;

class SortByCount extends Stage
{
    /** @var string */
    private $fieldName;

    /**
     * @param string $fieldName Expression to group by. To specify a field path,
     * prefix the field name with a dollar sign $ and enclose it in quotes.
     * The expression can not evaluate to an object.
     */
    public function __construct(Builder $builder, string $fieldName, DocumentManager $documentManager, ClassMetadata $class)
    {
        parent::__construct($builder);

        $documentPersister = $documentManager->getUnitOfWork()->getDocumentPersister($class->name);
        $this->fieldName   = '$' . $documentPersister->prepareFieldName(substr($fieldName, 1));
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression(): array
    {
        return [
            '$sortByCount' => $this->fieldName,
        ];
    }
}
