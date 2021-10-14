<?php

namespace GoFlink\Client\Data;

class Coordinate
{
    /**
     * Properties.
     */
    protected float $latitude;
    protected float $longitude;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_LATITUDE = "latitude";
    protected const DATA_KEY_LONGITUDE = "longitude";

    /**
     * @param float $latitude
     * @param float $longitude
     */
    public function __construct(float $latitude, float $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @param array $data
     *
     * @return Coordinate
     */
    public static function createFromData(array $data): Coordinate
    {
        return new Coordinate(
            $data[self::DATA_KEY_LATITUDE],
            $data[self::DATA_KEY_LONGITUDE]
        );
    }
}