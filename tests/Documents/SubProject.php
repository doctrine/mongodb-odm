<?php

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class SubProject extends Project
{
    /**
     * @ODM\EmbedMany(targetDocument="Documents\Issue")
     */
    private $issues;

    public function getIssues()
    {
        return $this->issues;
    }

    public function setIssues(Collection $issues)
    {
        $this->issues = $issues;
    }
}