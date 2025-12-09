<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'embedded_test')]
class EmbeddedTestLevel0
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, EmbeddedTestLevel1>|array<EmbeddedTestLevel1> */
    #[ODM\EmbedMany(targetDocument: EmbeddedTestLevel1::class)]
    public $level1 = [];
}
