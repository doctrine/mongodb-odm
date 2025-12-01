<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Int64;

use function array_is_list;
use function is_array;
use function sprintf;

/**
 * @phpstan-type Vector list<int|Int64>|list<float|Decimal128>|list<bool|0|1>|Binary
 * @phpstan-type VectorSearchStageExpression array{
 *     "$vectorSearch": object{
 *         exact?: bool,
 *         filter?: object,
 *         index?: string,
 *         limit?: int,
 *         numCandidates?: int,
 *         path?: string,
 *         queryVector?: Vector,
 *     }
 * }
 */
class VectorSearch extends Stage
{
    /** @see Binary::TYPE_VECTOR introduced in ext-mongodb 2.2 */
    private const BINARY_TYPE_VECTOR = 9;

    private ?bool $exact            = null;
    private array|Expr|null $filter = null;
    private ?string $index          = null;
    private ?int $limit             = null;
    private ?int $numCandidates     = null;
    private ?string $path           = null;
    /** @phpstan-var Vector|null */
    private array|Binary|null $queryVector = null;

    public function __construct(Builder $builder, private DocumentPersister $persister)
    {
        parent::__construct($builder);
    }

    public function getExpression(): array
    {
        $params = [];

        if ($this->exact !== null) {
            $params['exact'] = $this->exact;
        }

        if ($this->filter instanceof Expr) {
            $params['filter'] = $this->filter->getQuery();
        } elseif (is_array($this->filter)) {
            $params['filter'] = $this->filter;
        }

        if ($this->index !== null) {
            $params['index'] = $this->index;
        }

        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }

        if ($this->numCandidates !== null) {
            $params['numCandidates'] = $this->numCandidates;
        }

        if ($this->path !== null) {
            $params['path'] = $this->persister->prepareFieldName($this->path);
        }

        if ($this->queryVector !== null) {
            $params['queryVector'] = Type::getType($this->persister->getClassMetadata()->fieldMappings[$this->path ?? '']['type'] ?? Type::RAW)->convertToDatabaseValue($this->queryVector);
        }

        return [$this->getStageName() => $params];
    }

    public function exact(bool $exact): static
    {
        $this->exact = $exact;

        return $this;
    }

    /** @phpstan-param array<string, mixed>|Expr $filter */
    public function filter(array|Expr $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function index(string $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function numCandidates(int $numCandidates): static
    {
        $this->numCandidates = $numCandidates;

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /** @phpstan-param Vector $queryVector */
    public function queryVector(array|Binary $queryVector): static
    {
        if ($queryVector === []) {
            throw new InvalidArgumentException('Query vector cannot be an empty array.');
        }

        if (is_array($queryVector) && ! array_is_list($queryVector)) {
            throw new InvalidArgumentException('Query vector must be a list of numbers, got an associative array.');
        }

        if ($queryVector instanceof Binary && $queryVector->getType() !== self::BINARY_TYPE_VECTOR) {
            throw new InvalidArgumentException(sprintf('Binary query vector must be of type 9 (Vector), got %d.', $queryVector->getType()));
        }

        $this->queryVector = $queryVector;

        return $this;
    }

    protected function getStageName(): string
    {
        return '$vectorSearch';
    }
}
