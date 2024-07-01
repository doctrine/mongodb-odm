<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\Point;
use MongoDB\BSON\UTCDateTime;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/near/
 */
class Near extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    private int|float|UTCDateTime|array|Point|null $origin;

    private int|float|null $pivot;

    /** @var list<string> */
    private array $path;

    /**
     * @param int|float|UTCDateTime|array|Point|null $origin
     * @param int|float|null                         $pivot
     */
    public function __construct(Search $search, $origin = null, $pivot = null, string ...$path)
    {
        parent::__construct($search);

        $this
            ->origin($origin)
            ->pivot($pivot)
            ->path(...$path);
    }

    /** @param int|float|UTCDateTime|array|Point|null $origin */
    public function origin($origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    /** @param int|float|null $pivot */
    public function pivot($pivot): static
    {
        $this->pivot = $pivot;

        return $this;
    }

    public function path(string ...$path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'near';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [
            'origin' => $this->origin instanceof Geometry
                ? $this->origin->jsonSerialize()
                : $this->origin,
            'pivot' => $this->pivot,
            'path' => $this->path,
        ];

        return $this->appendScore($params);
    }
}
