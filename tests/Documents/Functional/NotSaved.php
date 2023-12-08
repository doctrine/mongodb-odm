<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'functional_tests')]
class NotSaved
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var string|null */
    #[ODM\Field(notSaved: true)]
    public $notSaved;

    /** @var NotSavedEmbedded|null */
    #[ODM\EmbedOne(targetDocument: NotSavedEmbedded::class)]
    public $embedded;
}
