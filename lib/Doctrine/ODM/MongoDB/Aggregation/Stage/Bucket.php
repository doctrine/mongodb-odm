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

class Bucket extends AbstractBucket
{
    /**
     * @var array
     */
    private $boundaries;

    /**
     * @var mixed
     */
    private $default;

    /**
     * An array of values based on the groupBy expression that specify the
     * boundaries for each bucket.
     *
     * Each adjacent pair of values acts as the inclusive lower boundary and the
     * exclusive upper boundary for the bucket. You must specify at least two
     * boundaries. The specified values must be in ascending order and all of
     * the same type. The exception is if the values are of mixed numeric types.
     *
     * @param array ...$boundaries
     *
     * @return $this
     */
    public function boundaries(...$boundaries)
    {
        $this->boundaries = $boundaries;
        return $this;
    }

    /**
     * A literal that specifies the _id of an additional bucket that contains
     * all documents whose groupBy expression result does not fall into a bucket
     * specified by boundaries.
     *
     * @param mixed $default
     *
     * @return $this
     */
    public function defaultBucket($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * A document that specifies the fields to include in the output documents
     * in addition to the _id field. To specify the field to include, you must
     * use accumulator expressions.
     *
     * @return Bucket\BucketOutput
     */
    public function output()
    {
        if (! $this->output) {
            $this->output = new Bucket\BucketOutput($this->builder, $this);
        }

        return $this->output;
    }

    protected function getExtraPipelineFields()
    {
        $fields = ['boundaries' => $this->boundaries];
        if ($this->default !== null) {
            $fields['default'] = $this->default;
        }

        return $fields;
    }

    protected function getStageName()
    {
        return '$bucket';
    }
}
