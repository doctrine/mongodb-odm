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

namespace Doctrine\ODM\MongoDB\Query;

/**
 * Utility class for merging query criteria.
 *
 * This is mainly used to incorporate filter and ReferenceMany mapping criteria
 * into a query. Each criteria array will be joined with "$and" to avoid cases
 * where criteria might be inadvertently overridden with array_merge().
 */
class CriteriaMerger
{
    /**
     * Combines any number of criteria arrays as clauses of an "$and" query.
     *
     * @param array $criteria,... Any number of query criteria arrays
     * @return array
     */
    public function merge(/* array($field => $value), ... */)
    {
        $merged = array();

        foreach (func_get_args() as $criteria) {
            if (empty($criteria)) {
                continue;
            }

            $merged['$and'][] = $criteria;
        }

        return (isset($merged['$and']) && count($merged['$and']) === 1)
            ? $merged['$and'][0]
            : $merged;
    }
}
