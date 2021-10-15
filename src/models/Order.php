<?php

namespace GoFlink\Client\Models;

use GoFlink\Client\Data\Address;
use GoFlink\Client\Response;

class Order extends Model
{
    /**
     * Properties.
     */
    protected string $id;
    protected string $status;
    protected Address $shippingAddress;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_ID = "id";
    protected const DATA_KEY_STATUS = "status";
    protected const DATA_KEY_SHIPPING_ADDRESS = "shipping_address";

    /**
     * @param string $id
     * @param string $status
     * @param Address $shippingAddress
     * @param array $data
     */
    public function __construct(
        string $id,
        string $status,
        Address $shippingAddress,
        array $data
    ) {
        parent::__construct($data);

        $this->id = $id;
        $this->status = $status;
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @param Response $response
     *
     * @return Order
     */
    public static function createFromApiResponse(Response $response): Order
    {
        self::assertCanCreateFromResponse(
            $response,
            [
                self::DATA_KEY_ID,
                self::DATA_KEY_STATUS,
                self::DATA_KEY_SHIPPING_ADDRESS,
            ]
        );

        $data = $response->getSingleDataElement();

        return new Order(
            $data[self::DATA_KEY_ID],
            $data[self::DATA_KEY_STATUS],
            Address::createFromData($data[self::DATA_KEY_SHIPPING_ADDRESS]),
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
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return Address
     */
    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }
}