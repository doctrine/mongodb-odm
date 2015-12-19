<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Documents\VersionedDocument
 *
 * @ODM\Document
 */
class VersionedDocument extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Version @ODM\Field(type="int") */
    public $version;
}
