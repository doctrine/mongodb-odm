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
 * Fluent interface for adding a $unwind stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.2
 */
class Unwind extends Stage
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $includeArrayIndex;

    /**
     * @var bool
     */
    private $preserveNullAndEmptyArrays = false;

    /**
     * @param Builder $builder
     * @param string $fieldName
     */
    public function __construct(Builder $builder, $fieldName)
    {
        parent::__construct($builder);

        $this->fieldName = (string) $fieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        // Fallback behavior for MongoDB < 3.2
        if ($this->includeArrayIndex === null && ! $this->preserveNullAndEmptyArrays) {
            return [
                '$unwind' => $this->fieldName
            ];
        }

        $unwind = ['path' => $this->fieldName];

        foreach (['includeArrayIndex', 'preserveNullAndEmptyArrays'] as $option) {
            if ( ! $this->$option) {
                continue;
            }

            $unwind[$option] = $this->$option;
        }

        return [
            '$unwind' => $unwind
        ];
    }

    /**
     * The name of a new field to hold the array index of the element. The name
     * cannot start with a dollar sign $.
     *
     * @param string $includeArrayIndex
     * @return $this
     *
     * @since 1.3
     */
    public function includeArrayIndex($includeArrayIndex)
    {
        $this->includeArrayIndex = $includeArrayIndex;

        return $this;
    }

    /**
     * If true, if the path is null, missing, or an empty array, $unwind outputs
     * the document.
     *
     * @param bool $preserveNullAndEmptyArrays
     * @return $this
     *
     * @since 1.3
     */
    public function preserveNullAndEmptyArrays($preserveNullAndEmptyArrays = true)
    {
        $this->preserveNullAndEmptyArrays = $preserveNullAndEmptyArrays;

        return $this;
    }
}
