<?php

namespace Documents;

/** @MappedSuperclass */
abstract class BaseCategory
{
    /** @String */
     protected $name;

     /** @EmbedMany(targetDocument="SubCategory") */
     protected $children = array();

     public function __construct($name = null)
     {
         $this->name = $name;
     }
    
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

     public function addChild(BaseCategory $child)
     {
         $this->children[] = $child;
     }

     public function getChildren()
     {
         return $this->children;
     }
}