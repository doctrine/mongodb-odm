<?php

namespace Documents\Ecommerce;

/** @EmbeddedDocument */
class Money
{
    /**
     * @Float
     */
    protected $amount;

    /**
     * @ReferenceOne(targetDocument="Documents\Ecommerce\Currency", cascade="all")
     */
    protected $currency;

    public function __construct($amount, Currency $currency)
    {
        $amount = (float) $amount;
        if (empty($amount) || $amount <= 0) {
            throw new \InvalidArgumentException(
                'money amount cannot be empty, equal or less than 0'
            );
        }
        $this->amount = $amount;
        $this->setCurrency($currency);
    }

    public function getAmount()
    {
        return $this->amount * $this->getCurrency()->getMultiplier();
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency)
    {
        $this->currency = $currency;
    }
}