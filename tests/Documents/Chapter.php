<?php

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 * @ODM\HasLifecycleCallbacks
 */
class Chapter
{
    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedMany(targetDocument="Page") */
    public $pages;

    /** @ODM\Field(type="int") */
    public $nbPages = 0;

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->pages = new ArrayCollection();
    }

    /**
     * @ODM\PostUpdate
     */
    public function doThisAfterAnUpdate()
    {
        /* Do not do this at home, it is here only to see if nothing breaks,
         * field will not be updated in database with new value unless another
         * flush() is made.
         */
        $this->nbPages = $this->pages->count();
    }
}
