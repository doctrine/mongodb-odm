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
* and is licensed under the LGPL. For more information, see
* <http://www.doctrine-project.org>.
*/

namespace Doctrine\ODM\MongoDB\Query\Filter;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetaData;

/**
* The base class that user defined filters should extend.
*
* Handles the setting and escaping of parameters.
*
* @author Tim Roediger <superdweebie@gmail.com>
* @abstract
*/
abstract class BsonFilter
{
    /**
     * The entity manager.
     * @var DocumentManager
     */
    private $dm;

    /**
     * Parameters for the filter.
     * @var array
     */
    private $parameters;

    /**
     * Constructs the BsonFilter object.
     *
     * @param DocumentManager $dm The Document Manager
     */
    final public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Sets a parameter that can be used by the filter.
     *
     * @param string $name Name of the parameter.
     * @param string $value Value of the parameter.
     *
     * @return BsonFilter The current Bson filter.
     */
    final public function setParameter($name, $value)
    {
        $this->parameters[$name] = array('value' => $value);

        // Keep the parameters sorted for the hash
        ksort($this->parameters);

        // The filter collection of the dm is now dirty
        $this->dm->getFilters()->setFiltersStateDirty();

        return $this;
    }

    /**
     * Gets the critera part to add to a query.
     *
     * @return array The criteria array, if there is available, empty array otherwise
     */
    abstract public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias);  
}