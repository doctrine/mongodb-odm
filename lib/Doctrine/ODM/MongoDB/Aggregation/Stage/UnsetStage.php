<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;

use function array_map;
use function array_values;

/**
 * Fluent interface for adding an $unset stage to an aggregation pipeline.
 *
 * @psalm-type UnsetStageExpression = array{'$unset': list<string>}
 */
class UnsetStage extends Stage
{
    private DocumentPersister $documentPersister;

    /** @var list<string> */
    private array $fields;

    public function __construct(Builder $builder, DocumentPersister $documentPersister, string ...$fields)
    {
        parent::__construct($builder);

        $this->documentPersister = $documentPersister;
        $this->fields            = array_values($fields);
    }

    /** @return UnsetStageExpression */
    public function getExpression(): array
    {
        return [
            '$unset' => array_map([$this->documentPersister, 'prepareFieldName'], $this->fields),
        ];
    }
}
