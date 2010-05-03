<?php

namespace Documents;

/**
 * @Document
 * @InheritanceType("COLLECTION_PER_CLASS")
 * @DiscriminatorMap={"comment"="Documents\Comment", "my_comment"="Documents\MyComment"})
 */
abstract class BaseDocument
{
    /** @Id */
    protected $id;

    /** @Field */
    protected $name;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}