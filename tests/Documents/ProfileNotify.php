<?php

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document @ODM\ChangeTrackingPolicy("NOTIFY") */
class ProfileNotify implements NotifyPropertyChanged
{
    /** @ODM\Id */
    private $profileId;

    /** @ODM\Field */
    private $firstName;

    /** @ODM\Field */
    private $lastName;

    /** @ODM\ReferenceOne(targetDocument="File", cascade={"all"}) */
    private $image;

    /** @ODM\ReferenceMany(targetDocument="File", cascade={"all"}, collectionClass="ProfileNotifyImagesCollection") */
    private $images;

    /** @var PropertyChangedListener[] */
    private $listeners = array();

    public function __construct()
    {
        $this->images = new ProfileNotifyImagesCollection();
    }

    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->listeners[] = $listener;
    }

    private function propertyChanged($propName, $oldValue, $newValue)
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $propName, $oldValue, $newValue);
        }
    }

    public function getProfileId()
    {
        return $this->profileId;
    }

    public function setFirstName($firstName)
    {
        $this->propertyChanged('firstName', $this->firstName, $firstName);
        $this->firstName = $firstName;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setLastName($lastName)
    {
        $this->propertyChanged('lastName', $this->lastName, $lastName);
        $this->lastName = $lastName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setImage(File $image)
    {
        $this->propertyChanged('image', $this->image, $image);
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getImages()
    {
        return $this->images;
    }
}

class ProfileNotifyImagesCollection extends ArrayCollection
{
    public function move($i, $j)
    {
        $tmp = $this->get($i);
        $this->set($i, $this->get($j));
        $this->set($j, $tmp);
    }
}
