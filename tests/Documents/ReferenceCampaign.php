<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class ReferenceCampaign
{
    /**
     * @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign",
     *     storeAs="id", inversedBy="referenceCampaigns",
     *     name="campaignId"
     * )
     */
    protected $campaign;
}
