<?php

namespace Documents;

/**
 * @Document
 */
class MyComment extends Comment
{
    /** @Field */
    private $test;

    public function setTest($test)
    {
        $this->test = $test;
    }

    public function getTest()
    {
        return $this->test;
    }
}