<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\Indexes({
 *   @ODM\Index(keys={"country"="asc", "zip"="asc", "city"="asc"})
 * })
 */
class CmsAddress
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
    public $country;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $zip;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $city;

    /**
     * @ODM\ReferenceOne(targetDocument=CmsUser::class)
     *
     * @var CmsUser|null
     */
    public $user;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getZipCode()
    {
        return $this->zip;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setUser(CmsUser $user): void
    {
        if ($this->user === $user) {
            return;
        }

        $this->user = $user;
        $user->setAddress($this);
    }
}
