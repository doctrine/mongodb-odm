<?php

namespace Documents;

/**
 * @Document(db="my_db", collection="projects")
 * @InheritanceType("SINGLE_COLLECTION")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({"project"="Documents\Project", "sub-project"="Documents\SubProject", "other-sub-project"="Documents\OtherSubProject"})
 */
class Project
{
    /** @Id */
    private $id;

    /** @String */
    private $name;

    /** @EmbedOne(targetDocument="Address") */
    private $address;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
    }

    public function getAddress()
    {
        return $this->address;
    }
}