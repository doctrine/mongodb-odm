<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Page
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var int */
    #[ODM\Field(type: 'int')]
    public $number;

    public function __construct(int $number)
    {
        $this->number = $number;
    }
}
