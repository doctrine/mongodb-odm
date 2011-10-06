<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="test_functional_virtual_host")
 *
 */
class VirtualHost
{

    /** @ODM\Id */
    protected $id;
    /**
     * @ODM\EmbedOne(targetDocument="Documents\Functional\VirtualHostDirective")
     */
    protected $vhostDirective;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Documents\Functional\VirtualHostDirective
     */
    public function getVHostDirective()
    {
        if (!$this->vhostDirective) {
            $this->vhostDirective = new VirtualHostDirective('VirtualHost', '*:80');
        }
        return $this->vhostDirective;
    }

    public function setVHostDirective($value)
    {
        $this->vhostDirective = $value;

        return $this;
    }

}

