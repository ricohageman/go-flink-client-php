<?php

namespace GoFlink\Client\Models;

use GoFlink\Client\Data\Address;
use GoFlink\Client\Data\Coordinate;
use GoFlink\Client\Data\Line;
use GoFlink\Client\Data\Price;
use GoFlink\Client\Response;

class Cart extends Model
{
    /**
     * Properties.
     */
    protected string $id;
    protected Price $totalPrice;
    protected Price $shippingPrice;
    protected Price $discount;
    protected Price $tip;
    protected Price $recyclingDeposit;
    protected array $lines;
    protected ?string $orderId;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_ID = "id";
    protected const DATA_KEY_TOTAL_PRICE = "total_price";
    protected const DATA_KEY_SHIPPING_PRICE = "shipping_price";
    protected const DATA_KEY_DISCOUNT = "discount";
    protected const DATA_KEY_TIP = 'rider_tip';
    protected const DATA_KEY_RECYCLING_DEPOSIT = 'recycling_deposit';
    protected const DATA_KEY_LINES = "lines";
    protected const DATA_KEY_ORDER = "order";
    protected const DATA_KEY_ORDER_ID = "id";

    /**
     * @param string $id
     * @param Price $totalPrice
     * @param Price $shippingPrice
     * @param Price $discount
     * @param Price $tip
     * @param Line[] $lines
     * @param array $data
     */
    public function __construct(
        string $id,
        Price $totalPrice,
        Price $shippingPrice,
        Price $discount,
        Price $tip,
        Price $recyclingDeposit,
        array $lines,
        array $data
    ) {
        parent::__construct($data);

        $this->id = $id;
        $this->totalPrice = $totalPrice;
        $this->shippingPrice = $shippingPrice;
        $this->discount = $discount;
        $this->tip = $tip;
        $this->recyclingDeposit = $recyclingDeposit;
        $this->lines = $lines;

        $this->addOrderIfPresent($data);
    }

    /**
     * @param array $data
     */
    private function addOrderIfPresent(array $data): void
    {
        $this->orderId = null;

        if (isset($data[self::DATA_KEY_ORDER]) == false) {
            // There is no order to add, continue.
        } else {
            $this->orderId = $data[self::DATA_KEY_ORDER][self::DATA_KEY_ORDER_ID];
        }
    }

    /**
     * @param Response $response
     *
     * @return Cart
     */
    public static function createSingleFromApiResponse(Response $response): Cart
    {
        self::assertCanCreateFromResponse(
            $response,
            [
                self::DATA_KEY_ID,
                self::DATA_KEY_TOTAL_PRICE,
                self::DATA_KEY_SHIPPING_PRICE,
                self::DATA_KEY_DISCOUNT,
                self::DATA_KEY_TIP,
                self::DATA_KEY_RECYCLING_DEPOSIT,
                self::DATA_KEY_LINES,
            ]
        );

        $data = $response->getSingleDataElement();

        return new Cart(
            $data[self::DATA_KEY_ID],
            Price::createFromData($data[self::DATA_KEY_TOTAL_PRICE]),
            Price::createFromData($data[self::DATA_KEY_SHIPPING_PRICE]),
            Price::createFromData($data[self::DATA_KEY_DISCOUNT]),
            Price::createFromData($data[self::DATA_KEY_TIP]),
            Price::createFromData($data[self::DATA_KEY_RECYCLING_DEPOSIT]),
            Line::createAllFromData($data[self::DATA_KEY_LINES]),
            $data
        );
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Price
     */
    public function getTotalPrice(): Price
    {
        return $this->totalPrice;
    }

    /**
     * @return Price
     */
    public function getShippingPrice(): Price
    {
        return $this->shippingPrice;
    }

    /**
     * @return Price
     */
    public function getDiscount(): Price
    {
        return $this->discount;
    }

    /**
     * @return Address
     */
    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    /**
     * @return Address
     */
    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    /**
     * @return Coordinate
     */
    public function getDeliveryCoordinate(): Coordinate
    {
        return $this->deliveryCoordinate;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return Line[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @param Address $billingAddress
     */
    public function setBillingAddress(Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    /**
     * @param Address $shippingAddress
     */
    public function setShippingAddress(Address $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @param Coordinate $deliveryCoordinate
     */
    public function setDeliveryCoordinate(Coordinate $deliveryCoordinate): void
    {
        $this->deliveryCoordinate = $deliveryCoordinate;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @param array $lines
     */
    public function setLines(array $lines): void
    {
        $this->lines = $lines;
    }

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @return bool
     */
    public function hasOrder(): bool
    {
        if (is_null($this->orderId)) {
            return false;
        } else {
            return true;
        }
    }
}