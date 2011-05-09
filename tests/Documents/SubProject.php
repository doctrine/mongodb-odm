<?php

namespace Documents;

use Doctrine\Common\Collections\Collection;

/** @Document */
class SubProject extends Project
{
    /**
     * @EmbedMany(targetDocument="Documents\Issue")
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