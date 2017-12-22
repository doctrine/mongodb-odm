<?php

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * Embeds multiple documents
 *
 * @Annotation
 */
final class EmbedMany extends AbstractField
{
    public $type = 'many';
    public $embedded = true;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
    public $defaultDiscriminatorValue;
    public $strategy = CollectionHelper::DEFAULT_STRATEGY;
    public $collectionClass;
}
