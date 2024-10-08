<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $collStats stage to an aggregation pipeline.
 *
 * @phpstan-type CollStatsStageExpression array{
 *     '$collStats': array{
 *         latencyStats?: array{histograms?: bool},
 *         storageStats?: array{},
 *     }
 * }
 */
class CollStats extends Stage
{
    public const LATENCY_STATS_NONE       = 0;
    public const LATENCY_STATS_SIMPLE     = 1;
    public const LATENCY_STATS_HISTOGRAMS = 2;

    private int $latencyStats = self::LATENCY_STATS_NONE;

    private bool $storageStats = false;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    /**
     * Adds latency statistics to the return document.
     */
    public function showLatencyStats(bool $histograms = false): static
    {
        $this->latencyStats = $histograms ? self::LATENCY_STATS_HISTOGRAMS : self::LATENCY_STATS_SIMPLE;

        return $this;
    }

    /**
     * Adds storage statistics to the return document.
     */
    public function showStorageStats(): static
    {
        $this->storageStats = true;

        return $this;
    }

    /** @phpstan-return CollStatsStageExpression */
    public function getExpression(): array
    {
        $collStats = [];
        if ($this->latencyStats !== self::LATENCY_STATS_NONE) {
            $collStats['latencyStats'] = ['histograms' => $this->latencyStats === self::LATENCY_STATS_HISTOGRAMS];
        }

        if ($this->storageStats) {
            $collStats['storageStats'] = [];
        }

        return ['$collStats' => $collStats];
    }
}
