<?php

namespace GoFlink\Client\Models;

use GoFlink\Client\Data\Coordinate;
use GoFlink\Client\Response;

class Address extends Model {
    /**
     * Properties.
     */
    protected string $id;
    protected string $street_address;
    protected string $post_code;
    protected string $city;
    protected string $country_code;
    protected Coordinate $coordinate;
    protected string $comment;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_ID = "id";
    protected const DATA_KEY_STREET_ADDRESS = "street_address";
    protected const DATA_KEY_POST_CODE = "post_code";
    protected const DATA_KEY_CITY = "city";
    protected const DATA_KEY_COUNTRY_CODE = "country_code";
    protected const DATA_KEY_COMMENT = "comment";
    protected const DATA_KEY_ADDRESS_LIST = "address_list";

    /**
     * @param string $id
     * @param string $street_address
     * @param string $post_code
     * @param string $city
     * @param string $country_code
     * @param Coordinate $coordinate
     * @param string $comment
     * @param array $data
     */
    protected function __construct(
        string $id,
        string $street_address,
        string $post_code,
        string $city,
        string $country_code,
        Coordinate $coordinate,
        string $comment,
        array $data
    ) {
        parent::__construct($data);

        $this->id = $id;
        $this->street_address = $street_address;
        $this->post_code = $post_code;
        $this->city = $city;
        $this->country_code = $country_code;
        $this->coordinate = $coordinate;
        $this->comment = $comment;
    }

    /**
     * @param Response $response
     *
     * @return Address[]
     */
    public static function createAllFromApiResponse(Response $response): array
    {
        $response->mutateData($response->getSingleDataElement()[self::DATA_KEY_ADDRESS_LIST]);

        self::assertCanCreateFromResponse(
            $response,
            [
                self::DATA_KEY_ID,
                self::DATA_KEY_STREET_ADDRESS,
                self::DATA_KEY_POST_CODE,
                self::DATA_KEY_CITY,
                self::DATA_KEY_COUNTRY_CODE,
                self::DATA_KEY_COMMENT,
            ]
        );

        $allAddress = [];

        foreach ($response->getData() as $data) {
            $allAddress[] = new Address(
                $data[self::DATA_KEY_ID],
                $data[self::DATA_KEY_STREET_ADDRESS],
                $data[self::DATA_KEY_POST_CODE],
                $data[self::DATA_KEY_CITY],
                $data[self::DATA_KEY_COUNTRY_CODE],
                Coordinate::createFromData($data),
                $data[self::DATA_KEY_COMMENT],
                $data
            );
        }

        return $allAddress;
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
    public function getStreetAddress(): string
    {
        return $this->street_address;
    }

    /**
     * @return string
     */
    public function getPostCode(): string
    {
        return $this->post_code;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->country_code;
    }

    /**
     * @return Coordinate
     */
    public function getCoordinate(): Coordinate
    {
        return $this->coordinate;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}