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

class BucketAuto extends AbstractBucket
{
    /**
     * @var int
     */
    private $buckets;

    /**
     * @var string
     */
    private $granularity;

    /**
     * A positive 32-bit integer that specifies the number of buckets into which
     * input documents are grouped.
     *
     * @param int $buckets
     *
     * @return $this
     */
    public function buckets($buckets)
    {
        $this->buckets = $buckets;
        return $this;
    }

    /**
     * A string that specifies the preferred number series to use to ensure that
     * the calculated boundary edges end on preferred round numbers or their
     * powers of 10.
     *
     * @param string $granularity
     *
     * @return $this
     */
    public function granularity($granularity)
    {
        $this->granularity = $granularity;
        return $this;
    }

    /**
     * A document that specifies the fields to include in the output documents
     * in addition to the _id field. To specify the field to include, you must
     * use accumulator expressions.
     *
     * @return Bucket\BucketAutoOutput
     */
    public function output()
    {
        if (! $this->output) {
            $this->output = new Bucket\BucketAutoOutput($this->builder, $this);
        }

        return $this->output;
    }

    protected function getExtraPipelineFields()
    {
        $fields = ['buckets' => $this->buckets];
        if ($this->granularity !== null) {
            $fields['granularity'] = $this->granularity;
        }

        return $fields;
    }

    protected function getStageName()
    {
        return '$bucketAuto';
    }
}
