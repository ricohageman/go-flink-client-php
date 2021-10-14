<?php

namespace GoFlink\Client\Data;

class Polygon
{
    /**
     * @var Coordinate[]
     */
    protected array $allCoordinate;

    /**
     * @param Coordinate[] $allCoordinate
     */
    public function __construct(array $allCoordinate)
    {
        $this->allCoordinate = $allCoordinate;
    }

    /**
     * @return Coordinate[]
     */
    public function getAllCoordinate(): array
    {
        return $this->allCoordinate;
    }
}