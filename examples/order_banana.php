<?php

require __DIR__ . '/../vendor/autoload.php';

use GoFlink\Client\Client;
use GoFlink\Client\Data\Coordinate;

// Coordinate of the new church in Delft, the Netherlands.
$coordinate = new Coordinate(52.012022198148784, 4.360051148682383);
$client = new Client();

// Find all the available hubs and select a hub by a randomly selected slug.
$all_hubs = $client->getAllHubs();
$selected_hub = $all_hubs[array_rand($all_hubs)];
$client->setHub($selected_hub);
echo vsprintf("Randomly selected hub '%s' which is located at (%s, %s)." . PHP_EOL,
    [
        $selected_hub->getSlug(),
        $selected_hub->getCoordinates()->getLatitude(),
        $selected_hub->getCoordinates()->getLongitude(),
    ],
);

// Find the hub serving the selected coordinate
$hub = $client->findHubByCoordinates($coordinate);
$client->setHub($hub);
echo vsprintf("Selected the hub '%s' to serve the coordinate (%s, %s)." . PHP_EOL,
    [
        $hub->getSlug(),
        $coordinate->getLatitude(),
        $coordinate->getLongitude(),
    ],
);

// Determine the duration it will take to delivery to a coordinate
$deliveryDuration = $client->determineDeliveryDurationToCoordinates($coordinate)->getSingleDataElement();
echo vsprintf(
    "It will take '%s' minutes (including '%s' minutes buffer time) to deliver from the hub '%s' to a location at the coordinate (%s, %s)" . PHP_EOL,
    [
        $deliveryDuration["delivery_time"] + $deliveryDuration["hub_buffer_time"],
        $deliveryDuration["hub_buffer_time"],
        $client->getCurrentlySetHub()->getSlug(),
        $coordinate->getLatitude(),
        $coordinate->getLongitude(),
    ],
);

// Get all the products available at the hub and select the banana by sku.
$products = $client->determineAllProducts();
echo vsprintf("There are '%s' unique products to buy at the selected hub." . PHP_EOL, [count($products)]);

$product = $products[13003041];

// Get the availability of the banana
$availability = $client->getAvailabilityOfProducts([$product])->getSingleDataElement();
echo vsprintf("There are currently '%s' bananas (%s) in stock at hub '%s'" . PHP_EOL,
    [
        $availability["quantity"],
        $availability["product_sku"],
        $client->getCurrentlySetHub()->getSlug(),
    ],
);
