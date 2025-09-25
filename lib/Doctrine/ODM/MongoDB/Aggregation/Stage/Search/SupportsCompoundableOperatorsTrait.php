<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedAutocomplete;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedEmbeddedDocument;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedEquals;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedExists;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedGeoShape;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedGeoWithin;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedMoreLikeThis;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedNear;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedPhrase;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedQueryString;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedRange;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedRegex;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedText;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\Compound\CompoundedWildcard;
use GeoJson\Geometry\LineString;
use GeoJson\Geometry\MultiPolygon;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/** @internal */
trait SupportsCompoundableOperatorsTrait
{
    abstract protected function getSearchStage(): Search;

    abstract protected function getCompoundStage(): Compound;

    abstract protected function getAddOperatorClosure(): Closure;

    /**
     * @param T $operator
     *
     * @return T
     *
     * @template T of SearchOperator
     */
    abstract protected function addOperator(SearchOperator $operator): SearchOperator;

    public function autocomplete(string $path = '', string ...$query): CompoundedAutocomplete
    {
        return $this->addOperator(new CompoundedAutocomplete($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $path, ...$query));
    }

    public function embeddedDocument(string $path = ''): CompoundedEmbeddedDocument
    {
        return $this->addOperator(new CompoundedEmbeddedDocument($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $path));
    }

    /** @param string|int|float|ObjectId|UTCDateTime|null $value */
    public function equals(string $path = '', $value = null): CompoundedEquals
    {
        return $this->addOperator(new CompoundedEquals($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $path, $value));
    }

    public function exists(string $path): CompoundedExists
    {
        return $this->addOperator(new CompoundedExists($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $path));
    }

    /** @param LineString|Point|Polygon|MultiPolygon|array|null $geometry */
    public function geoShape($geometry = null, string $relation = '', string ...$path): CompoundedGeoShape
    {
        return $this->addOperator(new CompoundedGeoShape($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $geometry, $relation, ...$path));
    }

    public function geoWithin(string ...$path): CompoundedGeoWithin
    {
        return $this->addOperator(new CompoundedGeoWithin($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), ...$path));
    }

    /** @param array<string, mixed>|object $documents */
    public function moreLikeThis(...$documents): CompoundedMoreLikeThis
    {
        return $this->addOperator(new CompoundedMoreLikeThis($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), ...$documents));
    }

    /**
     * @param int|float|UTCDateTime|array|Point|null $origin
     * @param int|float|null                         $pivot
     */
    public function near($origin = null, $pivot = null, string ...$path): CompoundedNear
    {
        return $this->addOperator(new CompoundedNear($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $origin, $pivot, ...$path));
    }

    public function phrase(): CompoundedPhrase
    {
        return $this->addOperator(new CompoundedPhrase($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }

    public function queryString(string $query = '', string $defaultPath = ''): CompoundedQueryString
    {
        return $this->addOperator(new CompoundedQueryString($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $query, $defaultPath));
    }

    public function range(): CompoundedRange
    {
        return $this->addOperator(new CompoundedRange($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }

    public function regex(): CompoundedRegex
    {
        return $this->addOperator(new CompoundedRegex($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }

    public function text(): CompoundedText
    {
        return $this->addOperator(new CompoundedText($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }

    public function wildcard(): CompoundedWildcard
    {
        return $this->addOperator(new CompoundedWildcard($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }
}
