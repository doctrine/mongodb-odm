<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class View extends AbstractDocument
{
    public function __construct(
        public readonly ?string $db = null,
        public readonly ?string $view = null,
        public readonly ?string $rootClass = null,
        public readonly ?string $repositoryClass = null,
    ) {
    }
}
