<?php

require __DIR__ . '/../vendor/autoload.php';

use GoFlink\Client\Client;
use GoFlink\Client\Data\Address;
use GoFlink\Client\Data\Coordinate;
use GoFlink\Client\Data\Line;
use GoFlink\Client\Data\Name;

$coordinate = new Coordinate(52.011446027275156, 4.358461558901702);
$address = new Address(
    "Markt",
    "87",
    "2611GS",
    "Delft",
    "NL",
    new Name("John", "Doe"),
    "+31600000000"
);

$client = new Client();
$hub = $client->findHubByCoordinates($coordinate);
$client->setHub($hub);
$deliveryDuration = $client->determineDeliveryDurationToCoordinates($coordinate)->getSingleDataElement();
$products = $client->determineAllProducts();
$product = $products[13003041]; // Hardcoded sku of a banana
$availability = $client->getAvailabilityOfProducts([$product]);

$cart = $client->createCart(
    $address,
    $coordinate,
    [Line::createFromProduct($product, 3)], // Required to order at least three bananas to ensure the minimum price is reached.
    "info@ricohageman.nl",
    $address,
);

$client->addPromocode($cart, "FIRST");
$cart = $client->getCart($cart->getId());