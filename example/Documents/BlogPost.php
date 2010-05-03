<?php

namespace Documents;

/**
 * @Document
 */
class BlogPost extends Page
{
    /** @Teaser */
    private $teaser;

    /** @Field */
    private $body;

    public function setTeaser($teaser)
    {
        $this->teaser = $teaser;
    }

    public function getTeaser()
    {
        return $this->teaser;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }
}