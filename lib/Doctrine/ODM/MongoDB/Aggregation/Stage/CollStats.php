<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

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
