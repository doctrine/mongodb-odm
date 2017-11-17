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

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding an output specification to a bucket stage.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.5
 */
class BucketAutoOutput extends AbstractOutput
{
    /**
     * @param Builder $builder
     * @param Stage\BucketAuto $bucket
     */
    public function __construct(Builder $builder, Stage\BucketAuto $bucket)
    {
        parent::__construct($builder, $bucket);
    }

    /**
     * An expression to group documents by. To specify a field path, prefix the
     * field name with a dollar sign $ and enclose it in quotes.
     *
     * @return Stage\BucketAuto
     */
    public function groupBy($expression)
    {
        return $this->bucket->groupBy($expression);
    }

    /**
     * A positive 32-bit integer that specifies the number of buckets into which input documents are grouped.
     *
     * @param int $buckets
     *
     * @return Stage\BucketAuto
     */
    public function buckets($buckets)
    {
        return $this->bucket->buckets($buckets);
    }

    /**
     * A string that specifies the preferred number series to use to ensure that
     * the calculated boundary edges end on preferred round numbers or their
     * powers of 10.
     *
     * @param string $granularity
     *
     * @return Stage\BucketAuto
     */
    public function granularity($granularity)
    {
        return $this->bucket->granularity($granularity);
    }
}
