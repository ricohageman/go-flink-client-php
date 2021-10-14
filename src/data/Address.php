<?php

namespace GoFlink\Client\Data;

class Address
{
    /**
     * Properties.
     */
    protected string $street;
    protected string $housenumber;
    protected string $postalCode;
    protected string $city;
    protected string $country;
    protected Name $name;
    protected string $phone;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_STREET_ADDRESS = "street_address_1";
    protected const DATA_KEY_POSTAL_CODE = "postal_code";
    protected const DATA_KEY_CITY = "city";
    protected const DATA_KEY_COUNTRY = "country";
    protected const DATA_KEY_PHONE = "phone";

    /**
     * @param string $street
     * @param string $housenumber
     * @param string $postalCode
     * @param string $city
     * @param string $country
     * @param Name $name
     * @param string $phone
     */
    public function __construct(
        string $street,
        string $housenumber,
        string $postalCode,
        string $city,
        string $country,
        Name $name,
        string $phone
    ) {
        $this->street = $street;
        $this->housenumber = $housenumber;
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->country = $country;
        $this->name = $name;
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getHousenumber(): string
    {
        return $this->housenumber;
    }

    /**
     * @return string
     */
    public function getStreetAddressOne(): string
    {
        return vsprintf("%s %s", [$this->getStreet(), $this->getHousenumber()]);
    }

    /**
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
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
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return Name
     */
    public function getName(): Name
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param array $data
     *
     * @return Address
     */
    public static function createFromData(array $data): Address
    {
        $streetAddressSplitted = self::splitStreetAddress($data[self::DATA_KEY_STREET_ADDRESS]);

        return new Address(
            $streetAddressSplitted["street"],
            vsprintf("%s%s", [$streetAddressSplitted["number"], $streetAddressSplitted["numberAddition"]]),
            $data[self::DATA_KEY_POSTAL_CODE],
            $data[self::DATA_KEY_CITY],
            $data[self::DATA_KEY_COUNTRY],
            Name::createFromData($data),
            $data[self::DATA_KEY_PHONE]
        );
    }

    /**
     * @brief Splits an address string containing a street, number and number addition.
     *  Based on https://gist.github.com/benvds/350404
     *
     * @param string $streetAddress An address string containing a street, number (optional) and number addition (optional)
     *
     * @return array Data array with the following keys: street, number and numberAddition.
     */
    private static function splitStreetAddress(string $streetAddress): array
    {
        $aMatch         = array();
        $pattern        = '#^([\w[:punct:] ]+) ([0-9]{1,5})([\w[:punct:]\-/]*)$#';
        $matchResult    = preg_match($pattern, $streetAddress, $aMatch);

        $street         = (isset($aMatch[1])) ? $aMatch[1] : '';
        $number         = (isset($aMatch[2])) ? $aMatch[2] : '';
        $numberAddition = (isset($aMatch[3])) ? $aMatch[3] : '';

        return array('street' => $street, 'number' => $number, 'numberAddition' => $numberAddition);

    }
}