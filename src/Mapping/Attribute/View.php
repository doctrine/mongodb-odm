<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class View extends AbstractDocument
{
    public function __construct(
        public ?string $db = null,
        public ?string $view = null,
        public ?string $rootClass = null,
        public ?string $repositoryClass = null,
    ) {
    }
}
