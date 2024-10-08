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
 * @phpstan-type UnsetStageExpression array{'$unset': list<string>}
 */
class UnsetStage extends Stage
{
    /** @var list<string> */
    private array $fields;

    public function __construct(Builder $builder, private DocumentPersister $documentPersister, string ...$fields)
    {
        parent::__construct($builder);

        $this->fields = array_values($fields);
    }

    /** @return UnsetStageExpression */
    public function getExpression(): array
    {
        return [
            '$unset' => array_map([$this->documentPersister, 'prepareFieldName'], $this->fields),
        ];
    }
}
