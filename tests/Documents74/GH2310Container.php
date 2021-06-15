<?php

declare(strict_types=1);

namespace Documents74;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document()
 */
class GH2310Container
{
    /** @ODM\Id */
    public ?string $id;

    /** @ODM\EmbedOne(targetDocument=GH2310Embedded::class, nullable=true) */
    public ?GH2310Embedded $embedded;

    public function __construct(?string $id, ?GH2310Embedded $embedded)
    {
        $this->id       = $id;
        $this->embedded = $embedded;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class GH2310Embedded
{
    /** @ODM\Field(type="integer") */
    public int $value;
}
