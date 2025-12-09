<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class NotSavedEmbedded
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var string|null */
    #[ODM\Field(notSaved: true)]
    public $notSaved;
}
