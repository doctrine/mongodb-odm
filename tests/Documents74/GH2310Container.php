<?php

declare(strict_types=1);

namespace Documents74;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document()
 */
class GH2310Container
{
    /** @ODM\Id(strategy="none") */
    public int $id;

    /** @ODM\EmbedOne(targetDocument=GH2310Embedded::class, nullable=true) */
    public ?GH2310Embedded $embedded;

    public function __construct(int $id, ?GH2310Embedded $embedded)
    {
        $this->id = $id;
        $this->embedded = $embedded;
    }
}

class GH2310Embedded
{
    /** @ODM\Field(type="integer") */
    public int $value;
}
