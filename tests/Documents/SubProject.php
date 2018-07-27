<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class SubProject extends Project
{
    /** @ODM\EmbedMany(targetDocument=Issue::class) */
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
