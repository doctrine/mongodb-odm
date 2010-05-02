<?php

namespace Documents;

/**
 * @Document
 * @InheritanceType("SINGLE_COLLECTION")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({"page"="Documents\Page", "blog_post"="Documents\BlogPost"})
 */
abstract class Node
{
    /** @Id */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}