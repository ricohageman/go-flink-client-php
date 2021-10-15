<?php

namespace GoFlink\Client\Models;

use GoFlink\Client\Data\Coordinate;
use GoFlink\Client\Data\Polygon;
use GoFlink\Client\Response;

class Hub extends Model
{
    /**
     * Properties.
     */
    protected string $id;
    protected string $slug;
    protected Coordinate $coordinate;

    /**
     * Data key constants.
     */
    protected const DATA_KEY_SLUG = "slug";
    protected const DATA_KEY_ID = "id";
    protected const DATA_KEY_COORDINATES = "coordinates";
    protected const DATA_KEY_DETAILS = "details";
    protected const DATA_KEY_IS_CLOSED = "is_closed";
    protected const DATA_KEY_TURFS = "turfs";

    /**
     * @param string $id
     * @param string $slug
     * @param array $data
     */
    protected function __construct(string $id, string $slug, Coordinate $coordinate, array $data)
    {
        parent::__construct($data);

        $this->id = $id;
        $this->slug = $slug;
        $this->coordinate = $coordinate;
    }

    /**
     * @param Response $response
     *
     * @return Hub
     */
    public static function createFromApiResponse(Response $response): Hub
    {
        self::assertCanCreateFromResponse(
            $response,
            [
                self::DATA_KEY_ID,
                self::DATA_KEY_SLUG,
                self::DATA_KEY_COORDINATES,
                self::DATA_KEY_TURFS,
            ]
        );

        $data = $response->getSingleDataElement();

        return new Hub(
            $data[self::DATA_KEY_ID],
            $data[self::DATA_KEY_SLUG],
            Coordinate::createFromData($data[self::DATA_KEY_COORDINATES]),
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
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return Coordinate
     */
    public function getCoordinates(): Coordinate
    {
        return $this->coordinate;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->data[self::DATA_KEY_DETAILS][self::DATA_KEY_IS_CLOSED];
    }

    /**
     * @return Polygon
     */
    public function getPolygon(): Polygon
    {
        return new Polygon(Coordinate::createAllFromData($this->getData()[self::DATA_KEY_TURFS][0]));
    }
}