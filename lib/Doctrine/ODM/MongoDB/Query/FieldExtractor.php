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
 * Class responsible for extracting an array of field names that are involved in
 * a given mongodb query. Used for checking if query is indexed.
 *
 * @see Doctrine\ODM\MongoDB\Query::isIndexed()
 */
class FieldExtractor
{
    private $query;
    private $sort;

    public function __construct(array $query, array $sort = array())
    {
        $this->query = $query;
        $this->sort = $sort;
    }

    public function getFields()
    {
        $fields = array();

        foreach ($this->query as $k => $v) {
            if (is_array($v) && isset($v['$elemMatch']) && is_array($v['$elemMatch'])) {
                $elemMatchFields = $this->getFieldsFromElemMatch($v['$elemMatch']);
                foreach ($elemMatchFields as $field) {
                    $fields[] = $k.'.'.$field;
                }
            } elseif ($this->isOperator($k, array('and', 'or'))) {
                foreach ($v as $q) {
                    $test = new self($q);
                    $fields = array_merge($fields, $test->getFields());
                }
            } elseif ($k[0] !== '$') {
                $fields[] = $k;
            }
        }
        $fields = array_unique(array_merge($fields, array_keys($this->sort)));
        return $fields;
    }

    private function getFieldsFromElemMatch(array $elemMatch)
    {
        $fields = array();
        foreach ($elemMatch as $fieldName => $value) {
            if ($this->isOperator($fieldName, 'where')) {
                continue;
            }

            if ($this->isOperator($fieldName, array('and', 'or'))) {
                foreach ($value as $q) {
                    $test = new self($q);
                    $fields = array_merge($fields, $test->getFields());
                }
            } else {
                $fields[] = $fieldName;
            }
        }
        return $fields;
    }

    private function isOperator($fieldName, $operator)
    {
        if ( ! is_array($operator)) {
            $operator = array($operator);
        }
        foreach ($operator as $op) {
            if ($fieldName === '$' . $op) {
                return true;
            }
        }
        return false;
    }
}
