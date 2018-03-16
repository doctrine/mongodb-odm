<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Embeds a single document
 *
 * @Annotation
 */
final class EmbedOne extends AbstractField
{
    /** @var string */
    public $type = 'one';

    /** @var bool */
    public $embedded = true;

    /** @var string|null */
    public $targetDocument;

    /** @var string|null */
    public $discriminatorField;

    /** @var string|null */
    public $discriminatorMap;

    /** @var string|null */
    public $defaultDiscriminatorValue;
}
