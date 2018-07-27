<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="embedded_test") */
class EmbeddedTestLevel0b
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\EmbedOne(targetDocument=EmbeddedTestLevel1::class) */
    public $oneLevel1;
    /** @ODM\EmbedMany(targetDocument=EmbeddedTestLevel1::class) */
    public $level1 = [];
}
