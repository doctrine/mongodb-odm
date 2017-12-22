<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $collStats stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.5
 */
class CollStats extends Stage
{
    const LATENCY_STATS_NONE = 0;
    const LATENCY_STATS_SIMPLE = 1;
    const LATENCY_STATS_HISTOGRAMS = 2;

    /**
     * @var int
     */
    private $latencyStats = self::LATENCY_STATS_NONE;

    /**
     * @var bool
     */
    private $storageStats = false;

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    /**
     * Adds latency statistics to the return document.
     *
     * @param bool $histograms Adds latency histogram information to latencyStats if true.
     *
     * @return $this
     */
    public function showLatencyStats($histograms = false)
    {
        $this->latencyStats = $histograms ? self::LATENCY_STATS_HISTOGRAMS : self::LATENCY_STATS_SIMPLE;

        return $this;
    }

    /**
     * Adds storage statistics to the return document.
     *
     * @return $this
     */
    public function showStorageStats()
    {
        $this->storageStats = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
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
