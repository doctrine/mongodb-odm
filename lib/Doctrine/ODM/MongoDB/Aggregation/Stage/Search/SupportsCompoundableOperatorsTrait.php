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

    /** return Autocomplete&CompoundSearchOperatorInterface */
    public function autocomplete(string $path = '', string ...$query): Autocomplete
    {
        return $this->addOperator(new CompoundedAutocomplete($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $path, ...$query));
    }

    /** @return EmbeddedDocument&CompoundSearchOperatorInterface */
    public function embeddedDocument(string $path = ''): EmbeddedDocument
    {
        return $this->addOperator(new CompoundedEmbeddedDocument($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $path));
    }

    /**
     * @param string|int|float|ObjectId|UTCDateTime|null $value
     *
     * @return Equals&CompoundSearchOperatorInterface
     */
    public function equals(string $path = '', $value = null): Equals
    {
        return $this->addOperator(new CompoundedEquals($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $path, $value));
    }

    /** @return Exists&CompoundSearchOperatorInterface */
    public function exists(string $path): Exists
    {
        return $this->addOperator(new CompoundedExists($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $path));
    }

    /**
     * @param LineString|Point|Polygon|MultiPolygon|array|null $geometry
     *
     * @return GeoShape&CompoundSearchOperatorInterface
     */
    public function geoShape($geometry = null, string $relation = '', string ...$path): GeoShape
    {
        return $this->addOperator(new CompoundedGeoShape($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $geometry, $relation, ...$path));
    }

    /** @return GeoWithin&CompoundSearchOperatorInterface */
    public function geoWithin(string ...$path): GeoWithin
    {
        return $this->addOperator(new CompoundedGeoWithin($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), ...$path));
    }

    /**
     * @param array<string, mixed>|object $documents
     *
     * @return MoreLikeThis&CompoundSearchOperatorInterface
     */
    public function moreLikeThis(...$documents): MoreLikeThis
    {
        return $this->addOperator(new CompoundedMoreLikeThis($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), ...$documents));
    }

    /**
     * @param int|float|UTCDateTime|array|Point|null $origin
     * @param int|float|null                         $pivot
     *
     * @return Near&CompoundSearchOperatorInterface
     */
    public function near($origin = null, $pivot = null, string ...$path): Near
    {
        return $this->addOperator(new CompoundedNear($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $origin, $pivot, ...$path));
    }

    /** @return Phrase&CompoundSearchOperatorInterface */
    public function phrase(): Phrase
    {
        return $this->addOperator(new CompoundedPhrase($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }

    /** @return QueryString&CompoundSearchOperatorInterface */
    public function queryString(string $query = '', string $defaultPath = ''): QueryString
    {
        return $this->addOperator(new CompoundedQueryString($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $query, $defaultPath));
    }

    /** @return Range&CompoundSearchOperatorInterface */
    public function range(): Range
    {
        return $this->addOperator(new CompoundedRange($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }

    /** @return Regex&CompoundSearchOperatorInterface */
    public function regex(): Regex
    {
        return $this->addOperator(new CompoundedRegex($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }

    /** @return Text&CompoundSearchOperatorInterface */
    public function text(): Text
    {
        return $this->addOperator(new CompoundedText($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }

    /** @return Wildcard&CompoundSearchOperatorInterface */
    public function wildcard(): Wildcard
    {
        return $this->addOperator(new CompoundedWildcard($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage()));
    }
}
