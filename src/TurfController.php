<?php

namespace GoFlink\Client;

use GoFlink\Client\Data\Coordinate;
use GoFlink\Client\Data\Polygon;
use GoFlink\Client\Models\Hub;

/**
 * Class that is used to calculate whether a coordinate is within a set of coordinates.
 * Based on https://gist.github.com/vzool/e5ee5fab6608c7a9e82e2c4b800a86e3
 */
class TurfController
{
    public static function isCoordinateWithinHubDeliveryArea(Coordinate $coordinate, Hub $hub): bool
    {
        $polygon = $hub->getPolygon();

        if (self::isCoordinateOnPolygonVertex($coordinate, $polygon)) {
            return true;
        } else if (self::IsCoordinateInsidePolygon($coordinate, $polygon)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Coordinate $coordinate
     * @param Polygon $polygon
     *
     * @return bool
     */
    private static function isCoordinateOnPolygonVertex(Coordinate $coordinate, Polygon $polygon): bool
    {
        foreach ($polygon->getAllCoordinate() as $polygonVertex) {
            if (self::isCoordinateEqual($coordinate, $polygonVertex)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Coordinate $coordinate
     * @param Coordinate $coordinateOther
     *
     * @return bool
     */
    private static function isCoordinateEqual(Coordinate $coordinate, Coordinate $coordinateOther): bool
    {
        return $coordinateOther->getLongitude() == $coordinate->getLongitude()
            && $coordinateOther->getLatitude() == $coordinate->getLatitude();
    }

    /**
     * @param Coordinate $coordinate
     * @param Polygon $polygon
     *
     * @return bool
     */
    private static function IsCoordinateInsidePolygon(Coordinate $coordinate, Polygon $polygon): bool
    {
        $intersections = 0;
        $vertices_count = count($polygon->getAllCoordinate());

        for ($i=1; $i < $vertices_count; $i++) {
            $vertex1 = $polygon->getAllCoordinate()[$i-1];
            $vertex2 = $polygon->getAllCoordinate()[$i];

            if ($vertex1->getLatitude() == $vertex2->getLatitude() and $vertex1->getLatitude() == $coordinate->getLatitude() and $coordinate->getLongitude() > min($vertex1->getLongitude(), $vertex2->getLongitude()) and $coordinate->getLongitude() < max($vertex1->getLongitude(), $vertex2->getLongitude())) { // Check if point is on an horizontal polygon boundary
                return true;
            }
            if ($coordinate->getLatitude() > min($vertex1->getLatitude(), $vertex2->getLatitude()) and $coordinate->getLatitude() <= max($vertex1->getLatitude(), $vertex2->getLatitude()) and $coordinate->getLongitude() <= max($vertex1->getLongitude(), $vertex2->getLongitude()) and $vertex1->getLatitude() != $vertex2->getLatitude()) {
                $xinters = ($coordinate->getLatitude() - $vertex1->getLatitude()) * ($vertex2->getLongitude() - $vertex1->getLongitude()) / ($vertex2->getLatitude() - $vertex1->getLatitude()) + $vertex1->getLongitude();
                if ($xinters == $coordinate->getLongitude()) { // Check if point is on the polygon boundary (other than horizontal)
                    return true;
                }
                if ($vertex1->getLongitude() == $vertex2->getLongitude() || $coordinate->getLongitude() <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is odd, then it's in the polygon.
        if ($intersections % 2 != 0) {
            return true;
        } else {
            return false;
        }
    }
}