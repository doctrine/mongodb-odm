<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/** @internal */
trait ScoredSearchOperatorTrait
{
    private ?object $score = null;

    private function appendScore(object $params): object
    {
        if (! $this->score) {
            return $params;
        }

        $params->score = $this->score;

        return $params;
    }

    public function boostScore(?float $value = null, ?string $path = null, ?float $undefined = null): static
    {
        $boost = (object) [];
        if ($value !== null) {
            $boost->value = $value;
        }

        if ($path !== null) {
            $boost->path = $path;
        }

        if ($undefined !== null) {
            $boost->undefined = $undefined;
        }

        $this->score = (object) ['boost' => $boost];

        return $this;
    }

    public function constantScore(float $value): static
    {
        $this->score = (object) [
            'constant' => (object) ['value' => $value],
        ];

        return $this;
    }
}
