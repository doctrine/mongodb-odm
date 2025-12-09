<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Documents\VersionedDocument
 */
#[ODM\Document]
class VersionedDocument extends BaseDocument
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var int|null */
    #[ODM\Version]
    #[ODM\Field(type: 'int')]
    public $version;
}
