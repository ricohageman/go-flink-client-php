<?php

namespace GoFlink\Client\Data;

class Name
{
    /**
     * Properties.
     */
    protected string $firstName;
    protected string $lastName;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_FIRST_NAME = "first_name";
    protected const DATA_KEY_LAST_NAME = "last_name";

    /**
     * @param string $firstName
     * @param string $lastName
     */
    public function __construct(string $firstName, string $lastName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param array $data
     *
     * @return Name
     */
    public static function createFromData(array $data): Name
    {
        return new Name(
            $data[self::DATA_KEY_FIRST_NAME],
            $data[self::DATA_KEY_LAST_NAME],
        );
    }
}