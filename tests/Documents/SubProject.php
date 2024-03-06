<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class SubProject extends Project
{
    /** @var Collection<int, Issue> */
    #[ODM\EmbedMany(targetDocument: Issue::class)]
    private $issues;

    /** @return Collection<int, Issue> */
    public function getIssues(): Collection
    {
        return $this->issues;
    }

    /** @param Collection<int, Issue> $issues */
    public function setIssues(Collection $issues): void
    {
        $this->issues = $issues;
    }
}
