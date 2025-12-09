<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** For testing $geoNear and other geographic operators */
#[ODM\Document(collection: 'places')]
#[ODM\Index(keys: ['location' => '2dsphere'])]
class Place
{
    #[ODM\Id]
    public ?string $id;

    /** @param array{type:string, coordinates: list<float|int>} $location Geometry */
    public function __construct(
        #[ODM\Field]
        public string $name,
        #[ODM\Field]
        public array $location,
        #[ODM\Field]
        public string $category,
    ) {
    }
}
