<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\GH683;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="gh683_test") */
class ParentDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedOne(targetDocument=AbstractEmbedded::class)
     *
     * @var AbstractEmbedded|null
     */
    public $embedOne;

    /**
     * @ODM\EmbedMany(targetDocument=AbstractEmbedded::class)
     *
     * @var Collection<int, AbstractEmbedded>
     */
    public $embedMany;
}
