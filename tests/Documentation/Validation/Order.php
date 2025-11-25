<?php

declare(strict_types=1);

namespace Documentation\Validation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedMany;
use Doctrine\ODM\MongoDB\Mapping\Attribute\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;
use Doctrine\ODM\MongoDB\Mapping\Attribute\PreFlush;
use Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceOne;

#[Document]
#[HasLifecycleCallbacks]
class Order
{
    #[Id]
    public string $id;

    public function __construct(
        #[ReferenceOne(targetDocument: Customer::class)]
        public Customer $customer,
        /** @var Collection<int, OrderLine> */
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
