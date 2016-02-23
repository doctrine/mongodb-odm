<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users")
 * @ODM\InheritanceType("COLLECTION_PER_CLASS")
 */
class VersionedUser extends User
{
    /** @ODM\Field(type="int") @ODM\Version */
    protected $version;

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }
}
