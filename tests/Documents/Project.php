<?php

namespace Documents;

/** @Document(db="my_db", collection="projects")
 * @InheritanceType("SINGLE_COLLECTION")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({"project"="Documents\Project", "sub-project"="Documents\SubProject"})
 */
class Project
{
    /** @Id */
    private $id;

    /** @String */
    private $name;

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

    public function setName($val)
    {
        $this->name = $val;
        return $this;
    }
}