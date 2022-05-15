<?php
namespace GoFlink\Client;

use GoFlink\Client\Data\Coordinate;
use GoFlink\Client\Models\Hub;
use GoFlink\Client\Models\Product;

class Client extends BaseClient
{
    protected string $host = "https://consumer-api.goflink.com";
    protected int $timeoutInSeconds;

    protected ?Hub $hub = null;
    protected ?string $bearerToken = null;

    /**
     * Error constants.
     */
    protected const ERROR_AUTHENTICATION_FAILED = "Authenticating with username and password failed";
    protected const ERROR_AUTHENTICATION_REQUIRED = "It is required to authenticate before performing this operation.";
    protected const ERROR_HUB_IS_REQUIRED = "It is required to assign a hub before executing this action.";
    protected const ERROR_HUB_IS_CLOSED = "The selected hub is currently closed, please wait until the hub opens before performing hub specific actions.";
    protected const ERROR_HUB_DOES_NOT_SERVE_AREA = "The provided delivery coordinate is outside the delivery area of the selected hub.";

    protected const ERROR_FAILED_TO_CREATE_CART = "An error occurred during the creation of a cart: %s";
    protected const ERROR_FAILED_TO_UPDATE_CART = "An error occurred during updating the cart.: %s";
    protected const ERROR_CART_HAS_NO_ORDER = "The provided cart has no reference to an order.";

    /**
     * URI's
     */
    protected const URL_DELIVERY_AREAS = 'delivery_areas';
    protected const URL_FIND_HUB_BY_COORDINATES = 'locations/hub';
    protected const URL_DETERMINE_DELIVERY_DURATION_TO_COORDINATES = 'delivery_time';
    protected const URI_GET_ALL_PRODUCTS = 'products';
    protected const URI_GET_PRODUCT_AVAILABILITY = 'products/amounts-by-sku';


    /**
     * API versions
     */
    protected const API_VERSION_1 = "v1";

    /**
     *
     */

    /**
     * Endpoints not requiring hub specification.
     */
    protected const ALL_URIS_NOT_REQUIRING_HUB_SELECTED = [
        self::URL_DELIVERY_AREAS => true,
        self::URL_FIND_HUB_BY_COORDINATES => true,
    ];

    /**
     * Endpoints not requiring hub to be opened.
     */
    protected const ALL_URIS_NOT_REQUIRING_HUB_OPENED = self::ALL_URIS_NOT_REQUIRING_HUB_SELECTED + [
        self::URL_DETERMINE_DELIVERY_DURATION_TO_COORDINATES => true,
        self::URI_GET_ALL_PRODUCTS => true,
        self::URI_GET_PRODUCT_AVAILABILITY => true,
    ];

    /**
     * Endpoints not requiring authentication.
     */
    private const ALL_URIS_NOT_REQUIRING_AUTHENTICATION = [
        self::URL_DELIVERY_AREAS => true,
        self::URL_FIND_HUB_BY_COORDINATES => true,
        self::URL_DETERMINE_DELIVERY_DURATION_TO_COORDINATES => true,
        self::URI_GET_ALL_PRODUCTS => true,
        self::URI_GET_PRODUCT_AVAILABILITY => true,
    ];

    /**
     * Header name.
     */
    const HEADER_LOCALE = 'locale';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_USER_AGENT = 'User-Agent';
    const HEADER_NAME_HUB_ID = 'hub';
    const HEADER_NAME_HUB_SLUG = 'hub-slug';
    const HEADER_NAME_AUTHORIZATION = 'Authorization';

    /**
     * Header value constants.
     */
    const HEADER_LOCALE_DEFAULT = 'en-NL';
    const HEADER_CONTENT_TYPE_DEFAULT = 'application/json';
    const HEADER_USER_AGENT_DEFAULT = 'Flink/1.0.0 (Client)';

    /**
     * @param int $timeout_in_seconds
     */
    function __construct(int $timeout_in_seconds = 10) {
        $this->timeoutInSeconds = $timeout_in_seconds;
    }

    /**
     * @return Hub[]
     */
    public function getAllHubs(): array
    {
        $all_delivery_area_information_per_country = $this->sendRequest(
            self::API_VERSION_1,
            self::URL_DELIVERY_AREAS,
            [],
            self::METHOD_GET,
            [],
        )->getData();

        $all_hubs = [];

        foreach ($all_delivery_area_information_per_country as $all_delivery_area_information) {
            foreach ($all_delivery_area_information["cities"] as $all_delivery_area_information_per_city) {
                foreach ($all_delivery_area_information_per_city["delivery_areas"] as $delivery_area) {
                    $all_hubs[$delivery_area["slug"]] = new Hub(
                        $delivery_area["id"],
                        $delivery_area["slug"],
                        $delivery_area + [
                            "city" => $all_delivery_area_information_per_city["id"],
                            "city_name" => $all_delivery_area_information_per_city["name"],
                            "country" => $all_delivery_area_information["id"],
                            "coordinates" => $delivery_area["default_location"]
                        ]
                    );
                }
            }
        }

        return $all_hubs;
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
                self::API_VERSION_1,
                self::URL_FIND_HUB_BY_COORDINATES,
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
            self::API_VERSION_1,
            self::URL_DETERMINE_DELIVERY_DURATION_TO_COORDINATES,
            [],
            self::METHOD_GET,
            [
                "hub_coords" => vsprintf(
                    "%s,%s",
                    [
                        $this->getCurrentlySetHub()->getCoordinate()->getLatitude(),
                        $this->getCurrentlySetHub()->getCoordinate()->getLongitude(),
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
        return Product::createFromApiResponse(
            $this->sendRequest(
                self::API_VERSION_1,
                self::URI_GET_ALL_PRODUCTS,
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
        return $this->getAvailabilityOfProductsBySku($this->determineAllProductSkuByAllProduct($allProduct));
    }

    /**
     * @param string[] $allProductSku
     *
     * @return Response
     */
    public function getAvailabilityOfProductsBySku(array $allProductSku): Response
    {
        return $this->sendRequest(
            self::API_VERSION_1,
            self::URI_GET_PRODUCT_AVAILABILITY,
            ["product_skus" => $allProductSku],
            self::METHOD_POST,
            []
        );
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
     * @param Hub $hub
     */
    public function setHub(Hub $hub): void
    {
        $this->hub = $hub;
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
        if ($this->requiresEndpointHub($endpoint) && $this->requiresEndpointOpenedHub($endpoint)) {
            $this->assertHubIsOpened();
        } else {
            // Endpoint is excepted from requirements, continue.
        }
    }

    /**
     * @throws Exception
     */
    private function assertHubIsOpened(): void
    {
        $this->hub = $this->getHubByIdentifier($this->getCurrentlySetHub()->getId());

        if ($this->getCurrentlySetHub()->isClosed()) {
            throw new Exception(self::ERROR_HUB_IS_CLOSED);
        } else {
            // Hub is still open, continue.
        }
    }

    /**
     * @param Coordinate $coordinate
     *
     * @throws Exception
     */
    private function assertCoordinateIsWithinDeliveryAreaOfHub(Coordinate $coordinate): void
    {
        $this->assertHubIsSet();

        if (TurfController::isCoordinateWithinHubDeliveryArea($coordinate, $this->getCurrentlySetHub())) {
            // The coordinate is within the delivery area of the hub, continue.
        } else {
            throw new Exception(self::ERROR_HUB_DOES_NOT_SERVE_AREA);
        }
    }

    /**
     * @param string $id
     *
     * @return Hub
     */
    public function getHubByIdentifier(string $id): Hub
    {
        return Hub::createFromApiResponse(
            $this->sendRequest(
                vsprintf("hubs/%s", [$id]),
                [],
                self::METHOD_GET,
                [],
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
        if (isset(self::ALL_URIS_NOT_REQUIRING_HUB_SELECTED[$endpoint])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $endpoint
     *
     * @return bool
     */
    private function requiresEndpointOpenedHub(string $endpoint): bool
    {
        if (isset(self::ALL_URIS_NOT_REQUIRING_HUB_OPENED[$endpoint])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $endpoint
     */
    private function assertAuthenticatedIfRequired(string $endpoint): void
    {
        if ($this->requiresEndpointAuthentication($endpoint)) {
            $this->assertAuthenticated();
        } else {
            // Endpoint requires no authentication, continue.
        }
    }

    /**
     * @param string $endpoint
     * @return bool
     */
    private function requiresEndpointAuthentication(string $endpoint): bool
    {
        if (isset(self::ALL_URIS_NOT_REQUIRING_AUTHENTICATION[$endpoint])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @throws Exception
     */
    private function assertAuthenticated(): void {
        if (is_null($this->bearerToken)) {
            throw new Exception(self::ERROR_AUTHENTICATION_REQUIRED);
        }
    }

    public function authenticate(string $email, string $password)
    {
        $url = "https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key=AIzaSyB1d_TI3VVh6c0QHe_jOTph4hvMOydZyvg";
        $params = [
            "email" => $email,
            "password" => $password,
            "returnSecureToken" => true,
        ];

        $response = parent::executeRequest(
            $url,
            $this->timeoutInSeconds,
            [],
            $params,
            self::METHOD_POST,
            false
        );

        if ($response->isError()) {
            throw new Exception(self::ERROR_AUTHENTICATION_FAILED);
        }

        $this->authenticateWithBearerToken($response->getSingleDataElement()["idToken"]);
    }

    /**
     * @param string $bearerToken
     * @return void
     */
    public function authenticateWithBearerToken(string $bearerToken)
    {
        $this->bearerToken = $bearerToken;
    }

    /**
     * @param string $api_version
     * @param string $endpoint
     * @param array $params
     * @param string $method
     * @param array $filters
     *
     * @return Response
     */
    protected function sendRequest(
        string $api_version,
        string $endpoint,
        array $params = [],
        string $method = self::METHOD_GET,
        array $filters = []
    ): Response {
        $this->assertHubIsSetIfRequired($endpoint);
        $this->assertHubIsOpenedIfRequired($endpoint);
        $this->assertAuthenticatedIfRequired($endpoint);
        $endpoint = $this->getEndpoint($endpoint, $filters);

        return parent::executeRequest(
            $this->getUrl($api_version, $endpoint),
            $this->timeoutInSeconds,
            $this->determineAllHeaders(),
            $params,
            $method,
        );
    }

    /**
     * @return string[]
     */
    private function determineAllHeaders(): array
    {
        $headers = [];
        $headers = array_merge($this->determineDefaultHeaders(), $headers);
        $headers = array_merge($this->determineHubHeaders(), $headers);
        $headers = array_merge($this->determineAuthorizationHeaders(), $headers);

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
     * @return string[][]
     */
    protected function determineAuthorizationHeaders(): array
    {
        $headers = [];

        if (is_null($this->bearerToken)) {
            // Not authorized, so no headers to set.
        } else {
            $headers[self::HEADER_NAME_AUTHORIZATION] = vsprintf("Bearer %s", [$this->bearerToken]);
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
     * @param string $endpoint
     *
     * @return string
     */
    protected function getUrl(string $api_version, string $endpoint): string
    {
        return $this->host . '/' . $api_version . '/' . $endpoint;
    }

    /**
     * @param string $endpoint
     * @param string[] $filters
     *
     * @return string
     */
    protected function getEndpoint(string $endpoint, array $filters): string
    {
        if (empty($filters)) {
            return $endpoint;
        }

        $endpoint .= "?";

        foreach ($filters as $key => $value) {
            $endpoint .= vsprintf("%s=%s&", [$key, urlencode($value)]);
        }

        return $endpoint;
    }
}


class BaseClient {
    protected bool $skipSslVerification = true;

    /**
     * Method constants.
     */
    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const METHOD_PUT = "PUT";
    const METHOD_DELETE = "DELETE";

    protected function executeRequest(
        string $url,
        int $timeout_in_seconds,
        array $headers = [],
        array $params = [],
        string $method = self::METHOD_GET,
        bool $json_format = true
    ): Response {
        $curlSession = curl_init();

        curl_setopt($curlSession, CURLOPT_HEADER, false);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, $timeout_in_seconds);
        curl_setopt($curlSession, CURLOPT_HTTPHEADER, $headers);

        $this->setSslVerification($curlSession);

        if ($json_format) {
            $this->setJsonPostData($curlSession, $method, $params);
        } else {
            $this->setQueryPostData($curlSession, $method, $params);
        }


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
     * @param $curlSession
     * @param $method
     * @param $params
     */
    protected function setQueryPostData($curlSession, $method, $params): void
    {
        if (!in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE])) {
            return;
        }

        curl_setopt($curlSession, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    /**
     * @param $curlSession
     * @param $method
     * @param $params
     */
    protected function setJsonPostData($curlSession, $method, $params): void
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
}