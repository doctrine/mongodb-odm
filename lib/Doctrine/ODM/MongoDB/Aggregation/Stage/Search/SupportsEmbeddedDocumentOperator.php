<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsEmbeddedDocumentOperator
{
    public function embeddedDocument(string $path = ''): EmbeddedDocument;
}
