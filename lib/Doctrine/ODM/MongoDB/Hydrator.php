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

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\EventManager;

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection,
    Doctrine\ODM\MongoDB\Event\LifecycleEventArgs,
    Doctrine\ODM\MongoDB\Event\PreLoadEventArgs,
    Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;

/**
 * The Hydrator class is responsible for converting a document from MongoDB
 * which is an array to classes and collections based on the mapping of the document
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Hydrator
{
    /**
     * The DocumentManager associated with this Hydrator
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The HydratorFactory instance used for generating hydrators.
     *
     * @var Doctrine\ODM\MongoDB\Hydrator\Hydrator
     */
    private $hydratorFactory;

    /**
     * The EventManager associated with this Hydrator
     *
     * @var Doctrine\Common\EventManager
     */
    private $evm;

    /**
     * Mongo command prefix
     * @var string
     */
    private $cmd;

    /**
     * Create a new Hydrator instance
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Doctrine\Common\EventManager $evm
     * @param string $cmd
     */
    public function __construct(DocumentManager $dm, HydratorFactory $hydratorFactory, EventManager $evm, $cmd)
    {
        $this->dm = $dm;
        $this->hydratorFactory = $hydratorFactory;
        $this->evm = $evm;
        $this->cmd = $cmd;
    }

    /**
     * Hydrate array of MongoDB document data into the given document object.
     *
     * @param object $document  The document object to hydrate the data into.
     * @param array $data The array of document data.
     * @return array $values The array of hydrated values.
     */
    public function hydrate($document, $data)
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));
        // Invoke preLoad lifecycle events and listeners
        if (isset($metadata->lifecycleCallbacks[Events::preLoad])) {
            $args = array(&$data);
            $metadata->invokeLifecycleCallbacks(Events::preLoad, $document, $args);
        }
        if ($this->evm->hasListeners(Events::preLoad)) {
            $this->evm->dispatchEvent(Events::preLoad, new PreLoadEventArgs($document, $this->dm, $data));
        }

        // Use the alsoLoadMethods on the document object to transform the data before hydration
        if (isset($metadata->alsoLoadMethods)) {
            foreach ($metadata->alsoLoadMethods as $fieldName => $method) {
                if (isset($data[$fieldName])) {
                    $document->$method($data[$fieldName]);
                }
            }
        }

        $data = $this->hydratorFactory->getHydratorFor($metadata->name)->hydrate($document, $data);

        // Invoke the postLoad lifecycle callbacks and listeners
        if (isset($metadata->lifecycleCallbacks[Events::postLoad])) {
            $metadata->invokeLifecycleCallbacks(Events::postLoad, $document);
        }
        if ($this->evm->hasListeners(Events::postLoad)) {
            $this->evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($document, $this->dm));
        }

        return $data;
    }
}