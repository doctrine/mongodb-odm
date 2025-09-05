<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\VectorSearchIndex;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;

#[Document(collection: 'vector_embeddings')]
#[VectorSearchIndex(
    fields: [
        ['type' => 'vector', 'path' => 'vectorFloat', 'numDimensions' => 3, 'similarity' => ClassMetadata::VECTOR_SIMILARITY_DOT_PRODUCT],
    ],
)]
#[VectorSearchIndex(
    name: 'vector_int',
    fields: [
        ['type' => 'vector', 'path' => 'vectorInt', 'numDimensions' => 3, 'similarity' => ClassMetadata::VECTOR_SIMILARITY_COSINE],
        ['type' => 'filter', 'path' => 'filterField'],
    ],
)]
class VectorEmbedding
{
    #[Id]
    public ?string $id = null;

    /** @var list<float> */
    #[Field(type: Type::COLLECTION)]
    public array $vectorFloat = [];

    /** @var list<int> */
    #[Field(type: Type::COLLECTION)]
    public array $vectorInt = [];

    #[Field(type: Type::STRING)]
    public string $filterField;
}
