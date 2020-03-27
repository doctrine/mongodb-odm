<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="campaigns")
 * @ODM\InheritanceType("COLLECTION_PER_CLASS")
 */
class Campaign
{
    /** @ODM\ReferenceMany(targetDocument="Promo\Documents\Webhook", storeAs="id", mappedBy="campaign")*/
    protected $referenceCampaigns;
}
