<?php

namespace GoFlink\Client\Data;

use GoFlink\Client\Models\Product;

class Line
{
    /**
     * Properties.
     */
    protected string $productSku;
    protected int $quantity;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_PRODUCT_SKU = "product_sku";
    protected const DATA_KEY_QUANTITY = "quantity";

    /**
     * @param Product $product
     * @param int $quantity
     */
    public function __construct(string $productSku, int $quantity)
    {
        $this->productSku = $productSku;
        $this->quantity = $quantity;
    }

    /**
     * @param Product $product
     * @param int $quantity
     *
     * @return Line
     */
    public static function createFromProduct(Product $product, int $quantity = 1): Line
    {
        return new Line(
            $product->getSku(),
            $quantity
        );
    }

    /**
     * @param array $data
     *
     * @return Line[]
     */
    public static function createAllFromData(array $data): array
    {
        $allLine = [];

        foreach ($data as $row)
        {
            $allLine[] = new Line($row[self::DATA_KEY_PRODUCT_SKU], $row[self::DATA_KEY_QUANTITY]);
        }

        return $allLine;
    }

    /**
     * @return string
     */
    public function getProductSku(): string
    {
        return $this->productSku;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }
}