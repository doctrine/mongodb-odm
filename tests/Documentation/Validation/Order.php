<?php

declare(strict_types=1);

namespace Documentation\Validation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbedMany;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PreFlush;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceOne;

#[Document]
#[HasLifecycleCallbacks]
class Order
{
    #[Id]
    public string $id;

    public function __construct(
        #[ReferenceOne(targetDocument: Customer::class)]
        public Customer $customer,
        /** @var Collection<OrderLine> */
        #[EmbedMany(targetDocument: OrderLine::class)]
        public Collection $orderLines = new ArrayCollection(),
    ) {
    }

    /** @throw CustomerOrderLimitExceededException */
    #[PreFlush]
    public function assertCustomerAllowedBuying(): void
    {
        $orderLimit = $this->customer->orderLimit;

        $amount = 0;
        foreach ($this->orderLines as $line) {
            $amount += $line->amount;
        }

        if ($amount > $orderLimit) {
            throw new CustomerOrderLimitExceededException();
        }
    }
}
