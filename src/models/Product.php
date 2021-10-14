<?php

namespace GoFlink\Client\Models;

use GoFlink\Client\Response;

class Product extends Model
{
    protected string $id;
    protected string $sku;
    protected string $name;
    protected string $currency;
    protected string $amount;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_ID = "id";
    protected const DATA_KEY_NAME = "name";
    protected const DATA_KEY_SKU = "sku";
    protected const DATA_KEY_PRICE = "price";
    protected const DATA_KEY_PRICE_AMOUNT = "amount";
    protected const DATA_KEY_PRICE_CURRENCY = "currency";

    protected function __construct(string $id, string $sku, string $name, array $price, array $data)
    {
        parent::__construct($data);

        $this->id = $id;
        $this->sku = $sku;
        $this->name = $name;
        $this->currency = $price[self::DATA_KEY_PRICE_CURRENCY];
        $this->amount = $price[self::DATA_KEY_PRICE_AMOUNT];
    }

    /**
     * @param Response $response
     *
     * @return Product[]
     */
    public static function createFromApiResponse(Response $response): array
    {
        self::assertCanCreateFromResponse(
            $response,
            [
                self::DATA_KEY_ID,
                self::DATA_KEY_SKU,
                self::DATA_KEY_NAME,
                self::DATA_KEY_PRICE
            ]
        );

        $allProduct = [];

        foreach ($response->getData() as $data) {
            $allProduct[$data[self::DATA_KEY_SKU]] = new Product(
                $data[self::DATA_KEY_ID],
                $data[self::DATA_KEY_SKU],
                $data[self::DATA_KEY_NAME],
                $data[self::DATA_KEY_PRICE],
                $data
            );
        }

        return $allProduct;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed|string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return mixed|string
     */
    public function getAmount()
    {
        return $this->amount;
    }
}