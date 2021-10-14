<?php
namespace GoFlink\Client;

use GoFlink\Client\Data\Address;
use GoFlink\Client\Data\Coordinate;
use GoFlink\Client\Data\Line;
use GoFlink\Client\Models\Cart;
use GoFlink\Client\Models\Hub;
use GoFlink\Client\Models\Product;

class Client
{
    protected string $host = "https://consumer-api.goflink.com";
    protected string $version = "v1";

    protected bool $skipSslVerification = true;
    protected int $timeoutInSeconds = 10;

    protected ?Hub $hub = null;

    /**
     * Error constants.
     */
    protected const ERROR_HUB_IS_REQUIRED = "It is required to assign a hub before executing this action.";
    protected const ERROR_HUB_IS_ALREADY_SET = "There has already been an hub assigned to this client.";
    protected const ERROR_HUB_IS_CLOSED = "The selected hub is currently closed, please wait until the hub opens before performing hub specific actions.";
    protected const ERROR_FAILED_TO_CREATE_CART = "An error occurred during the creation of a cart: %s";
    protected const ERROR_FAILED_TO_UPDATE_CART = "An error occurred during updating the cart.: %s";

    /**
     * Endpoints not requiring
     */
    protected const ALL_URIS_NOT_REQUIRING_HUB_SELECTED = [
        self::URL_DELIVERY_AREAS => true,
        self::URL_LOCATIONS_HUB => true,
        self::URL_HUBS => true,
    ];
    protected const URL_DELIVERY_AREAS = 'delivery_areas';
    protected const URL_LOCATIONS_HUB = 'locations';
    protected const URL_HUBS = 'hubs';

    /**
     * Method constants.
     */
    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const METHOD_PUT = "PUT";
    const METHOD_DELETE = "DELETE";

    /**
     * Header name.
     */
    const HEADER_LOCALE = 'locale';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_USER_AGENT = 'User-Agent';
    const HEADER_NAME_HUB_ID = 'hub';
    const HEADER_NAME_HUB_SLUG = 'hub-slug';

    /**
     * Header value constants.
     */
    const HEADER_LOCALE_DEFAULT = 'en-NL';
    const HEADER_CONTENT_TYPE_DEFAULT = 'application/json';
    const HEADER_USER_AGENT_DEFAULT = 'Flink/1.0.0 (Client)';

    /**
     */
    public function getAllDeliveryAreas(): array
    {
        throw new Exception("This request is not yet implemented, an example request is as following: " . PHP_EOL .
            "\tGET https://consumer-api.goflink.com/v1/delivery_areas HTTP/2.0" . PHP_EOL);
    }

    /**
     * @param Coordinate $coordinate
     *
     * @return Hub
     */
    public function findHubByCoordinates(Coordinate $coordinate): Hub
    {
        return Hub::createFromApiResponse(
            $this->sendRequest(
                "locations/hub",
                [],
                self::METHOD_GET,
                [
                    "lat" => $coordinate->getLatitude(),
                    "long" => $coordinate->getLongitude(),
                ]
            )
        );
    }

    /**
     * @param Coordinate $coordinate
     *
     * @return Response
     */
    public function determineDeliveryDurationToCoordinates(Coordinate $coordinate): Response
    {
        $this->assertHubIsSet();

        return $this->sendRequest(
            "delivery_time",
            [],
            self::METHOD_GET,
            [
                "hub_coords" => vsprintf(
                    "%s,%s",
                    [
                        $this->getCurrentlySetHub()->getCoordinates()->getLatitude(),
                        $this->getCurrentlySetHub()->getCoordinates()->getLongitude(),
                    ]
                ),
                "delivery_coords" => vsprintf(
                    "%s,%s",
                    [
                        $coordinate->getLatitude(),
                        $coordinate->getLongitude()
                    ]
                ),
            ]
        );
    }

    /**
     * @return Product[]
     */
    public function determineAllProducts(): array
    {
        return  Product::createFromApiResponse(
            $this->sendRequest(
                "products",
                [],
                self::METHOD_GET,
                []
            )
        );
    }

    /**
     * @param Product[] $allProduct
     *
     * @return Response
     */
    public function getAvailabilityOfProducts(array $allProduct): Response
    {
        return $this->sendRequest(
            "products/amounts-by-sku",
            [
                "product_ids" => $this->determineAllProductIdByAllProduct($allProduct),
                "product_skus" => $this->determineAllProductSkuByAllProduct($allProduct),
            ],
            self::METHOD_POST,
            []
        );
    }

    /**
     * @param Product[] $allProduct
     *
     * @return string[]
     */
    private function determineAllProductIdByAllProduct(array $allProduct): array
    {
        $allProductId = [];

        foreach ($allProduct as $product)
        {
            $allProductId[] = $product->getId();
        }

        return $allProductId;
    }

    /**
     * @param Product[] $allProduct
     *
     * @return string[]
     */
    private function determineAllProductSkuByAllProduct(array $allProduct): array
    {
        $allProductId = [];

        foreach ($allProduct as $product)
        {
            $allProductId[] = $product->getSku();
        }

        return $allProductId;
    }

    /**
     * @param Address $billingAddress
     * @param Coordinate $deliveryCoordinate
     * @param Line[] $lines
     * @param string $email
     * @param Address $shippingAddress
     * @return Cart
     * @throws Exception
     */
    public function createCart(
        Address $billingAddress,
        Coordinate $deliveryCoordinate,
        array $lines,
        string $email,
        Address $shippingAddress
    ): Cart {
        $response = $this->sendRequest(
            "cart",
            [
                "billing_address" => [
                    "city" => $billingAddress->getCity(),
                    "country" => $billingAddress->getCountry(),
                    "first_name" => $billingAddress->getName()->getFirstName(),
                    "last_name" => $billingAddress->getName()->getLastName(),
                    "phone" => $billingAddress->getPhone(),
                    "postal_code" => $billingAddress->getPostalCode(),
                    "street_address_1" => $billingAddress->getStreetAddressOne(),
                ],
                "delivery_coordinates" => [
                    "latitude" => $deliveryCoordinate->getLatitude(),
                    "longitude" => $deliveryCoordinate->getLongitude(),
                ],
                "delivery_eta" => "10",
                "lines" => $this->determineAllLines($lines),
                "email" => $email,
                "shipping_address" => [
                    "city" => $shippingAddress->getCity(),
                    "country" => $shippingAddress->getCountry(),
                    "first_name" => $shippingAddress->getName()->getFirstName(),
                    "last_name" => $shippingAddress->getName()->getLastName(),
                    "phone" => $shippingAddress->getPhone(),
                    "postal_code" => $shippingAddress->getPostalCode(),
                    "street_address_1" => $shippingAddress->getStreetAddressOne(),
                ],
            ],
            self::METHOD_POST,
            []
        );

        if ($response->isError()) {
            throw new Exception(vsprintf(self::ERROR_FAILED_TO_CREATE_CART, [$response->getErrorMessage()]));
        } else {
            return $this->getCart($response->getSingleDataElement()["id"]);
        }
    }

    /**
     * @param Line[] $lines
     *
     * @return string[][]
     */
    private function determineAllLines(array $lines): array
    {
        $allLines = [];

        foreach ($lines as $line)
        {
            $allLines[] = [
                "product_sku" => $line->getProductSku(),
                "quantity" => $line->getQuantity(),
            ];
        }

        return $allLines;
    }

    /**
     * @param Cart $cart
     *
     * @return Cart
     */
    public function updateCart(Cart $cart): Cart
    {
        $response = $this->sendRequest(
            vsprintf("cart/%s", [$cart->getId()]),
            [
                "billing_address" => [
                    "city" => $cart->getBillingAddress()->getCity(),
                    "country" => $cart->getBillingAddress()->getCountry(),
                    "first_name" => $cart->getBillingAddress()->getName()->getFirstName(),
                    "last_name" => $cart->getBillingAddress()->getName()->getLastName(),
                    "phone" => $cart->getBillingAddress()->getPhone(),
                    "postal_code" => $cart->getBillingAddress()->getPostalCode(),
                    "street_address_1" => $cart->getBillingAddress()->getStreetAddressOne(),
                ],
                "delivery_coordinates" => [
                    "latitude" => $cart->getDeliveryCoordinate()->getLatitude(),
                    "longitude" => $cart->getDeliveryCoordinate()->getLongitude(),
                ],
                "delivery_eta" => "10",
                "lines" => $this->determineAllLines($cart->getLines()),
                "email" => $cart->getEmail(),
                "shipping_address" => [
                    "city" => $cart->getShippingAddress()->getCity(),
                    "country" => $cart->getShippingAddress()->getCountry(),
                    "first_name" => $cart->getShippingAddress()->getName()->getFirstName(),
                    "last_name" => $cart->getShippingAddress()->getName()->getLastName(),
                    "phone" => $cart->getShippingAddress()->getPhone(),
                    "postal_code" => $cart->getShippingAddress()->getPostalCode(),
                    "street_address_1" => $cart->getShippingAddress()->getStreetAddressOne(),
                ],
            ],
            self::METHOD_PUT,
            []
        );

        if ($response->isError()) {
            var_dump($response);
            throw new Exception(vsprintf(self::ERROR_FAILED_TO_UPDATE_CART, [$response->getErrorMessage()]));
        } else {
            return $this->getCart($cart->getId());
        }
    }

    /**
     * @param string $id
     *
     * @return Cart
     */
    public function getCart(string $id): Cart
    {
        return Cart::createFromApiResponse(
            $this->sendRequest(
                vsprintf("cart/%s", [$id]),
                [],
                self::METHOD_GET,
                []
            )
        );
    }

    /**
     * @param Cart $cart
     * @param string $promocode
     *
     * @return Response
     */
    public function addPromocode(Cart $cart, string $promocode): Response
    {
        return $this->sendRequest(
            vsprintf("cart/%s/add-promo-code", [$cart->getId()]),
            [
                "voucher_code" => $promocode,
            ],
            self::METHOD_POST,
            []
        );
    }

    /**
     * @param Cart $cart
     * @param string $issuer
     *
     * @return Response
     */
    public function checkoutWithIdeal(Cart $cart, string $issuer): Response
    {
        return $this->sendRequest(
            vsprintf("cart/%s/checkout", [$cart->getId()]),
            [
                "amount" => $cart->getTotalPrice()->getAmount(),
                "token" => json_encode(
                    [
                        "paymentMethod" => ["type" => "ideal", "issuer" => $issuer],
                        "storePaymentMethod" => False,
                        "amount" => [
                            "currency" => $cart->getTotalPrice()->getCurrency(),
                            "value" => $cart->getTotalPrice()->getAmountInCents(),
                        ],
                        "returnUrl" => "https://www.ijsed.nl",
                        "additionalData" => ["allow3DS2" => True],
                        "channel" => "Web",
                        "deliveryAddress" => [
                            "city" => $cart->getShippingAddress()->getCity(),
                            "country" => $cart->getShippingAddress()->getCountry(),
                            "houseNumberOrName" => $cart->getShippingAddress()->getHouseNumber(),
                            "postalCode" => $cart->getShippingAddress()->getPostalCode(),
                            "street" => $cart->getShippingAddress()->getStreet()
                        ],
                        "billingAddress" => [
                            "city" => $cart->getBillingAddress()->getCity(),
                            "country" => $cart->getBillingAddress()->getCountry(),
                            "houseNumberOrName" => $cart->getBillingAddress()->getHouseNumber(),
                            "postalCode" => $cart->getBillingAddress()->getPostalCode(),
                            "street" => $cart->getBillingAddress()->getStreet()
                        ],
                        "shopperEmail" => $cart->getEmail(),
                    ],
                    JSON_UNESCAPED_UNICODE
                ),
            ],
            self::METHOD_POST,
            []
        );
    }

    /**
     * @param Hub $hub
     *
     * @throws Exception
     */
    public function setHub(Hub $hub): void
    {
        if ($this->isHubSet()) {
            throw new Exception(self::ERROR_HUB_IS_ALREADY_SET);
        } else {
            $this->hub = $hub;
        }
    }

    /**
     * @return Hub
     */
    public function getCurrentlySetHub(): Hub
    {
        return $this->hub;
    }

    /**
     */
    private function assertHubIsSetIfRequired(string $endpoint): void
    {
        if ($this->requiresEndpointHub($endpoint)) {
            $this->assertHubIsSet();
        } else {
            // Endpoint is excepted from requirements, continue.
        }
    }

    /**
     * @throws Exception
     */
    private function assertHubIsSet(): void
    {
        if ($this->isHubSet()) {
            // All good, continue.
        } else {
            throw new Exception(self::ERROR_HUB_IS_REQUIRED);
        }
    }

    /**
     * @return bool
     */
    public function isHubSet(): bool
    {
        return is_null($this->hub) == false;
    }

    /**
     * @param string $endpoint
     */
    private function assertHubIsOpenedIfRequired(string $endpoint): void
    {
        if ($this->requiresEndpointHub($endpoint)) {
            $this->assertHubIsOpened();
        } else {
            // Endpoint is excepted from requirements, continue.
        }
    }

    private function assertHubIsOpened(): void
    {
        $this->hub = $this->getHubByIdentifier($this->getCurrentlySetHub()->getId());

        if ($this->getCurrentlySetHub()->isClosed()) {
            throw new Exception(self::ERROR_HUB_IS_CLOSED);
        } else {
            // Hub is still open, continue.
        }
    }

    public function getHubByIdentifier(string $id): Hub
    {
        return Hub::createFromApiResponse(
            $this->sendRequest(
                vsprintf("hubs/%s", [$id]),
                [],
                self::METHOD_GET,
                [],
                true
            )
        );
    }

    /**
     * @param string $endpoint
     *
     * @return bool
     */
    private function requiresEndpointHub(string $endpoint): bool
    {
        if (isset(self::ALL_URIS_NOT_REQUIRING_HUB_SELECTED[strtok($endpoint, '/')])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string
     * @param array
     * @param string
     * @param array
     *
     * @return Response
     */
    public function sendRequest(
        string $endpoint,
        array $params = [],
        string $method = self::METHOD_GET,
        array $filters = []
    ): Response {
        $this->assertHubIsSetIfRequired($endpoint);
        $this->assertHubIsOpenedIfRequired($endpoint);
        $endpoint = $this->getEndpoint($endpoint, $filters);

        $curlSession = curl_init();

        curl_setopt($curlSession, CURLOPT_HEADER, false);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curlSession, CURLOPT_URL, $this->getUrl($endpoint));
        curl_setopt($curlSession, CURLOPT_TIMEOUT, $this->timeoutInSeconds);
        curl_setopt($curlSession, CURLOPT_HTTPHEADER, $this->determineAllHeaders());

        $this->setSslVerification($curlSession);
        $this->setPostData($curlSession, $method, $params);

        $apiResult = curl_exec($curlSession);
        $headerInfo = curl_getinfo($curlSession);

        if ($this->isCurlFailed($apiResult))
            $response = Response::createFromCurlError($curlSession);
        else
            $response = Response::createFromHttpResponse($headerInfo, $apiResult);

        curl_close($curlSession);

        return $response;
    }

    /**
     * @param $apiResult
     *
     * @return bool
     */
    private function isCurlFailed($apiResult): bool
    {
        return $apiResult === false;
    }

    /**
     * @return string[]
     */
    private function determineAllHeaders(): array
    {
        $headers = [];
        $headers = array_merge($this->determineDefaultHeaders(), $headers);
        $headers = array_merge($this->determineHubHeaders(), $headers);

        $headers = $this->parseHeaders($headers);

        return $headers;
    }

    /**
     * @return string[]
     */
    protected function determineDefaultHeaders(): array
    {
        return [
            self::HEADER_LOCALE => self::HEADER_LOCALE_DEFAULT,
            self::HEADER_USER_AGENT => self::HEADER_USER_AGENT_DEFAULT,
            self::HEADER_CONTENT_TYPE => self::HEADER_CONTENT_TYPE_DEFAULT,
        ];
    }

    /**
     * @return string[][]
     */
    protected function determineHubHeaders(): array
    {
        $headers = [];

        if ($this->isHubSet()) {
            $headers[self::HEADER_NAME_HUB_ID] = $this->hub->getId();
            $headers[self::HEADER_NAME_HUB_SLUG] = $this->hub->getSlug();
        } else {
            // No hub is selected, so no headers to set.
        }

        return $headers;
    }

    /**
     * @param string[] $headers
     * @return string[]
     */
    protected function parseHeaders(array $headers): array
    {
        $parsedHeaders = [];

        foreach ($headers as $key => $value)
        {
            $parsedHeaders[] = vsprintf("%s: %s", [$key, $value]);
        }

        return $parsedHeaders;
    }

    /**
     * @param $curlSession
     * @param $method
     * @param $params
     */
    protected function setPostData($curlSession, $method, $params): void
    {
        if (!in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE])) {
            return;
        }

        $data = json_encode($params);

        curl_setopt($curlSession, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
    }

    /**
     * @param $curlSession
     */
    protected function setSslVerification($curlSession): void
    {
        if ($this->skipSslVerification) {
            curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, false);
        }
    }

    /**
     * @param string $endpoint
     *
     * @return string
     */
    protected function getUrl(string $endpoint): string
    {
        return $this->host . '/' . $this->version . '/' . $endpoint;
    }

    /**
     * @param string $endpoint
     * @param string[] $filters
     *
     * @return string
     */
    protected function getEndpoint(string $endpoint, array $filters): string
    {
        if (! empty($filters)) {
            $i = 0;
            foreach ($filters as $key => $value) {
                if ($i == 0) {
                    $endpoint .= '?';
                } else {
                    $endpoint .= '&';
                }
                $endpoint .= $key . '=' . urlencode($value);
                $i++;
            }
        }

        return $endpoint;
    }
}