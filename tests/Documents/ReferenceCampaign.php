<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class ReferenceCampaign
{
    /** @ODM\Id */
    protected $id;

    /**
     * @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign",
     *     storeAs="id", inversedBy="referenceCampaigns",
     *     name="campaignId"
     * )
     */
    protected $campaign;

    public function getId()
    {
        return $this->id;
    }
}
