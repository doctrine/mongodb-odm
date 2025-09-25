<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use GeoJson\Geometry\LineString;
use GeoJson\Geometry\MultiPolygon;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

interface CompoundSearchOperatorInterface extends SupportsCompoundableSearchOperators
{
    public function must(): Compound;

    public function mustNot(): Compound;

    public function should(?int $minimumShouldMatch = null): Compound;

    public function filter(): Compound;

    public function autocomplete(string $path = '', string ...$query): Autocomplete&CompoundSearchOperatorInterface;

    public function embeddedDocument(string $path = ''): EmbeddedDocument&CompoundSearchOperatorInterface;

    /** @param string|int|float|ObjectId|UTCDateTime|null $value */
    public function equals(string $path = '', $value = null): Equals&CompoundSearchOperatorInterface;

    public function exists(string $path): Exists&CompoundSearchOperatorInterface;

    /** @param LineString|Point|Polygon|MultiPolygon|array<string, mixed>|null $geometry */
    public function geoShape($geometry = null, string $relation = '', string ...$path): GeoShape&CompoundSearchOperatorInterface;

    public function geoWithin(string ...$path): GeoWithin&CompoundSearchOperatorInterface;

    /** @param array<string, mixed>|object $documents */
    public function moreLikeThis(...$documents): MoreLikeThis&CompoundSearchOperatorInterface;

    /**
     * @param int|float|UTCDateTime|array<string, mixed>|Point|null $origin
     * @param int|float|null                                        $pivot
     */
    public function near($origin = null, $pivot = null, string ...$path): Near&CompoundSearchOperatorInterface;

    public function phrase(): Phrase&CompoundSearchOperatorInterface;

    public function queryString(string $query = '', string $defaultPath = ''): QueryString&CompoundSearchOperatorInterface;

    public function range(): Range&CompoundSearchOperatorInterface;

    public function regex(): Regex&CompoundSearchOperatorInterface;

    public function text(): Text&CompoundSearchOperatorInterface;

    public function wildcard(): Wildcard&CompoundSearchOperatorInterface;
}
