<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;
use Doctrine\ODM\MongoDB\Mapping\Attribute\VectorSearchIndex;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

#[Document(collection: 'auto_embedding_articles')]
#[VectorSearchIndex(
    fields: [
        [
            'type'     => 'autoEmbed',
            'path'     => 'content',
            'modality' => ClassMetadata::VECTOR_AUTOEMBEDDING_MODALITY_TEXT,
            'model'    => 'voyage-4-large',
        ],
        ['type' => 'filter', 'path' => 'category'],
    ],
)]
class AutoEmbeddingArticle
{
    #[Id]
    public ?string $id = null;

    #[Field]
    public string $content = '';

    #[Field]
    public string $category = '';
}
