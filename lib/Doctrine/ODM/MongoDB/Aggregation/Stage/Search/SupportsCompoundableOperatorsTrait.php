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
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use GeoJson\Geometry\LineString;
use GeoJson\Geometry\MultiPolygon;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/** @internal */
trait SupportsCompoundableOperatorsTrait
{
    abstract protected function getDocumentPersister(): DocumentPersister;

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

    public function autocomplete(string $path = '', string ...$query): Autocomplete&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedAutocomplete($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), $path, ...$query));
    }

    public function embeddedDocument(string $path = ''): EmbeddedDocument&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedEmbeddedDocument($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), $path));
    }

    /** @param string|int|float|ObjectId|UTCDateTime|null $value */
    public function equals(string $path = '', $value = null): Equals&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedEquals($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), $path, $value));
    }

    public function exists(string $path): Exists&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedExists($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), $path));
    }

    /** @param LineString|Point|Polygon|MultiPolygon|array<string, mixed>|null $geometry */
    public function geoShape($geometry = null, string $relation = '', string ...$path): GeoShape&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedGeoShape($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), $geometry, $relation, ...$path));
    }

    public function geoWithin(string ...$path): GeoWithin&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedGeoWithin($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), ...$path));
    }

    /** @param array<string, mixed>|object $documents */
    public function moreLikeThis(...$documents): MoreLikeThis&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedMoreLikeThis($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), ...$documents));
    }

    /**
     * @param int|float|UTCDateTime|array<string, mixed>|Point|null $origin
     * @param int|float|null                                        $pivot
     */
    public function near($origin = null, $pivot = null, string ...$path): Near&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedNear($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), $origin, $pivot, ...$path));
    }

    public function phrase(): Phrase&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedPhrase($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function queryString(string $query = '', string $defaultPath = ''): QueryString&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedQueryString($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister(), $query, $defaultPath));
    }

    public function range(): Range&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedRange($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function regex(): Regex&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedRegex($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function text(): Text&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedText($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function wildcard(): Wildcard&CompoundSearchOperatorInterface
    {
        return $this->addOperator(new CompoundedWildcard($this->getCompoundStage(), $this->getAddOperatorClosure(), $this->getSearchStage(), $this->getDocumentPersister()));
    }
}
