<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use GeoJson\Geometry\LineString;
use GeoJson\Geometry\MultiPolygon;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/** @internal */
trait SupportsAllSearchOperatorsTrait
{
    abstract protected function getDocumentPersister(): DocumentPersister;

    abstract protected function getSearchStage(): Search;

    /**
     * @param T $operator
     *
     * @return T
     *
     * @template T of SearchOperator
     */
    abstract protected function addOperator(SearchOperator $operator): SearchOperator;

    public function autocomplete(string $path = '', string ...$query): Autocomplete
    {
        return $this->addOperator(new Autocomplete($this->getSearchStage(), $this->getDocumentPersister(), $path, ...$query));
    }

    public function compound(): Compound
    {
        return $this->addOperator(new Compound($this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function embeddedDocument(string $path = ''): EmbeddedDocument
    {
        return $this->addOperator(new EmbeddedDocument($this->getSearchStage(), $this->getDocumentPersister(), $path));
    }

    /** @param string|int|float|ObjectId|UTCDateTime|null $value */
    public function equals(string $path = '', $value = null): Equals
    {
        return $this->addOperator(new Equals($this->getSearchStage(), $this->getDocumentPersister(), $path, $value));
    }

    public function exists(string $path): Exists
    {
        return $this->addOperator(new Exists($this->getSearchStage(), $this->getDocumentPersister(), $path));
    }

    /** @param LineString|Point|Polygon|MultiPolygon|array<string, mixed>|null $geometry */
    public function geoShape($geometry = null, string $relation = '', string ...$path): GeoShape
    {
        return $this->addOperator(new GeoShape($this->getSearchStage(), $this->getDocumentPersister(), $geometry, $relation, ...$path));
    }

    public function geoWithin(string ...$path): GeoWithin
    {
        return $this->addOperator(new GeoWithin($this->getSearchStage(), $this->getDocumentPersister(), ...$path));
    }

    /** @param array<string, mixed>|object $documents */
    public function moreLikeThis(...$documents): MoreLikeThis
    {
        return $this->addOperator(new MoreLikeThis($this->getSearchStage(), $this->getDocumentPersister(), ...$documents));
    }

    /**
     * @param int|float|UTCDateTime|array<string, mixed>|Point|null $origin
     * @param int|float|null                                        $pivot
     */
    public function near($origin = null, $pivot = null, string ...$path): Near
    {
        return $this->addOperator(new Near($this->getSearchStage(), $this->getDocumentPersister(), $origin, $pivot, ...$path));
    }

    public function phrase(): Phrase
    {
        return $this->addOperator(new Phrase($this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function queryString(string $query = '', string $defaultPath = ''): QueryString
    {
        return $this->addOperator(new QueryString($this->getSearchStage(), $this->getDocumentPersister(), $query, $defaultPath));
    }

    public function range(): Range
    {
        return $this->addOperator(new Range($this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function regex(): Regex
    {
        return $this->addOperator(new Regex($this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function text(): Text
    {
        return $this->addOperator(new Text($this->getSearchStage(), $this->getDocumentPersister()));
    }

    public function wildcard(): Wildcard
    {
        return $this->addOperator(new Wildcard($this->getSearchStage(), $this->getDocumentPersister()));
    }
}
