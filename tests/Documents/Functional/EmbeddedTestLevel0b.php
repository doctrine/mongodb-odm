<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="embedded_test") */
class EmbeddedTestLevel0b
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
     * @ODM\EmbedOne(targetDocument=EmbeddedTestLevel1::class)
     *
     * @var EmbeddedTestLevel1|null
     */
    public $oneLevel1;
    /**
     * @ODM\EmbedMany(targetDocument=EmbeddedTestLevel1::class)
     *
     * @var Collection<int, EmbeddedTestLevel1>|array<EmbeddedTestLevel1>
     */
    public $level1 = [];
}
