<?php

namespace GoFlink\Client\Data;

class Price
{
    /**
     * Properties.
     */
    protected string $currency;
    protected float $amount;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_AMOUNT = "amount";
    protected const DATA_KEY_CURRENCY = "currency";

    /**
     * @param string $currency
     * @param float $amount
     */
    public function __construct(string $currency, float $amount)
    {
        $this->currency = $currency;
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getAmountInCents(): int
    {
        return (int) ($this->amount * 100);
    }

    /**
     * @param array $data
     * @return Price
     */
    public static function createFromData(array $data): Price
    {
        return new Price(
            $data[self::DATA_KEY_CURRENCY],
            $data[self::DATA_KEY_AMOUNT],
        );
    }
}