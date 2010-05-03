<?php

namespace Documents;

/**
 * @Document
 */
class Page extends Node
{
    /** @Field */
    protected $title;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}