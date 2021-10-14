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
    protected Address $billingAddress;
    protected Address $shippingAddress;
    protected Coordinate $deliveryCoordinate;
    protected string $email;
    protected array $lines;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_ID = "id";
    protected const DATA_KEY_TOTAL_PRICE = "total_price";
    protected const DATA_KEY_SHIPPING_PRICE = "shipping_price";
    protected const DATA_KEY_DISCOUNT = "discount";
    protected const DATA_KEY_LINES = "lines";
    protected const DATA_KEY_BILLING_ADDRESS = "billing_address";
    protected const DATA_KEY_SHIPPING_ADDRESS = "shipping_address";
    protected const DATA_KEY_DELIVERY_COORDINATES = "delivery_coordinates";
    protected const DATA_KEY_EMAIL = "email";
    protected const DATA_KEY_PAYMENT_GATE_WAY = "payment_gateway";

    /**
     * @param string $id
     * @param Price $totalPrice
     * @param Price $shippingPrice
     * @param Price $discount
     * @param Address $billingAddress
     * @param Address $shippingAddress
     * @param Coordinate $deliveryCoordinate
     * @param string $email
     * @param Line[] $lines
     * @param array $data
     */
    public function __construct(
        string $id,
        Price $totalPrice,
        Price $shippingPrice,
        Price $discount,
        Address $billingAddress,
        Address $shippingAddress,
        Coordinate $deliveryCoordinate,
        string $email,
        array $lines,
        array $data
    ) {
        parent::__construct($data);

        $this->id = $id;
        $this->totalPrice = $totalPrice;
        $this->shippingPrice = $shippingPrice;
        $this->discount = $discount;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->deliveryCoordinate = $deliveryCoordinate;
        $this->email = $email;
        $this->lines = $lines;
    }

    /**
     * @param Response $response
     *
     * @return Cart
     */
    public static function createFromApiResponse(Response $response): Cart
    {
        self::assertCanCreateFromResponse(
            $response,
            [
                self::DATA_KEY_ID,
                self::DATA_KEY_TOTAL_PRICE,
                self::DATA_KEY_SHIPPING_PRICE,
                self::DATA_KEY_DISCOUNT,
                self::DATA_KEY_LINES,
                self::DATA_KEY_BILLING_ADDRESS,
                self::DATA_KEY_SHIPPING_ADDRESS,
                self::DATA_KEY_DELIVERY_COORDINATES,
                self::DATA_KEY_EMAIL,
                self::DATA_KEY_PAYMENT_GATE_WAY,
            ]
        );

        $data = $response->getSingleDataElement();

        return new Cart(
            $data[self::DATA_KEY_ID],
            Price::createFromData($data[self::DATA_KEY_TOTAL_PRICE]),
            Price::createFromData($data[self::DATA_KEY_SHIPPING_PRICE]),
            Price::createFromData($data[self::DATA_KEY_DISCOUNT]),
            Address::createFromData($data[self::DATA_KEY_BILLING_ADDRESS]),
            Address::createFromData($data[self::DATA_KEY_SHIPPING_ADDRESS]),
            Coordinate::createFromData($data[self::DATA_KEY_DELIVERY_COORDINATES]),
            $data[self::DATA_KEY_EMAIL],
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
}