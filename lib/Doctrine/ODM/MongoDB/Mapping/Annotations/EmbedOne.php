<?php

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Embeds a single document
 *
 * @Annotation
 */
final class EmbedOne extends AbstractField
{
    public $type = 'one';
    public $embedded = true;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
    public $defaultDiscriminatorValue;
}
