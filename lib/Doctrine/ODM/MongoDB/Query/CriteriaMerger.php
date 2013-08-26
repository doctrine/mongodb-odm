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
 * Class responsible for merging query criteria. Simple array_merge can't be used because
 * multiple critiera on the same field would be overwritten. This class will $and multiple critiera
 * on the same field together.
 *
 * @see Doctrine\ODM\MongoDB\Query::isIndexed()
 */
class CriteriaMerger
{
    /**
     * Acts just like array_merge() on query criteria. However, multiple criteria are defined for
     * the one field, the are merged such that the criteria will be $and ed together.
     *
     * @param array of arrays
     * @return array
     */
    public static function merge(){
        $listLength = func_num_args();
        $criteriaList = func_get_args();

        $return = $criteriaList[0];
        for ($i = 1; $i < $listLength; $i++){
            $criteria = $criteriaList[$i];
            foreach ($criteria as $field => $value){
                if (array_key_exists($field, $return)){
                    if (is_array($return[$field]) && !in_array($value, $return[$field])){
                        $return[$field][] = $value;
                    } elseif ($return[$field] !== $value){
                        $return[$field] = array($return[$field], $value);
                    }
                } else {
                    $return[$field] = $value;
                }
            }
        }
        return $return;
    }
}
