<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Documents\VersionedDocument
 *
 * @ODM\Document
 */
class VersionedDocument extends BaseDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Version
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    public $version;
}
