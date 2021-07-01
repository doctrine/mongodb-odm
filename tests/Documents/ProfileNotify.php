<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;

/** @ODM\Document @ODM\ChangeTrackingPolicy("NOTIFY") */
class ProfileNotify implements NotifyPropertyChanged
{
    /** @ODM\Id */
    private $profileId;

    /** @ODM\Field */
    private $firstName;

    /** @ODM\Field */
    private $lastName;

    /** @ODM\ReferenceOne(targetDocument=File::class, cascade={"all"}) */
    private $image;

    /** @ODM\ReferenceMany(targetDocument=File::class, cascade={"all"}, collectionClass=ProfileNotifyImagesCollection::class) */
    private $images;

    /** @var PropertyChangedListener[] */
    private $listeners = [];

    public function __construct()
    {
        $this->images = new ProfileNotifyImagesCollection();
    }

    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->listeners[] = $listener;
    }

    private function propertyChanged($propName, $oldValue, $newValue): void
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $propName, $oldValue, $newValue);
        }
    }

    public function getProfileId()
    {
        return $this->profileId;
    }

    public function setFirstName($firstName): void
    {
        $this->propertyChanged('firstName', $this->firstName, $firstName);
        $this->firstName = $firstName;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setLastName($lastName): void
    {
        $this->propertyChanged('lastName', $this->lastName, $lastName);
        $this->lastName = $lastName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setImage(File $image): void
    {
        $this->propertyChanged('image', $this->image, $image);
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getImages(): ProfileNotifyImagesCollection
    {
        return $this->images;
    }
}

class ProfileNotifyImagesCollection extends ArrayCollection
{
    public function move($i, $j): void
    {
        $tmp = $this->get($i);
        $this->set($i, $this->get($j));
        $this->set($j, $tmp);
    }
}
