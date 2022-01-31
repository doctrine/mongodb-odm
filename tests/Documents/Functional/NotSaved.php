<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class NotSaved
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
     * @ODM\Field(notSaved=true)
     *
     * @var string|null
     */
    public $notSaved;

    /**
     * @ODM\EmbedOne(targetDocument=NotSavedEmbedded::class)
     *
     * @var NotSavedEmbedded|null
     */
    public $embedded;
}
