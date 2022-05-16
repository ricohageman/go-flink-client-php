<?php

namespace GoFlink\Client;

class Response
{
    protected int $statusCode;
    protected string $message;
    protected array $data;

    /**
     * HTTP status code constants.
     */
    protected const ALL_STATUS_CODE_SUCCESS = [
        200,
        201,
        204,
    ];

    /**
     * Error constants.
     */
    protected const ERROR_MULTIPLE_ELEMENTS_IN_RESULT = "Multiple elements found while a single element was expected.";

    /**
     * @param int $statusCode
     * @param array $data
     * @param string $message
     */
    protected function __construct(int $statusCode, array $data, string $message)
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->message = $message;
    }

    /**
     * @param
     * @return Response
     */
    public static function createFromCurlError($curlSession): Response
    {
        return new Response(-1, [], curl_error($curlSession));
    }

    /**
     * @param array
     * @param mixed
     * @return Response
     */
    public static function createFromHttpResponse(array $headers, $body): Response
    {
        $bodyDecoded = json_decode($body, true);

        if (self::isValidJson($body) == false) {
            return new Response($headers['http_code'], [], $body);
        } else if (self::isArrayAssociative($bodyDecoded)) {
            return new Response($headers['http_code'], [$bodyDecoded], "");
        } else {
            return new Response($headers['http_code'], $bodyDecoded, "");
        }
    }

    /**
     * @param string $input
     *
     * @return bool
     */
    private static function isValidJson(string $input): bool
    {
        $json = json_decode($input);
        $inputParsed = str_replace('"', "", $input);

        if ($json === $inputParsed) {
            return false;
        } else if (json_last_error() == JSON_ERROR_NONE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    private static function isArrayAssociative(array $array): bool
    {
        if (array() === $array) {
            return false;
        } else {
            return array_keys($array) !== range(0, count($array) - 1);
        }
    }

    /**
     * @return mixed[]
     */
    public function getSingleDataElement(): array
    {
        $data = $this->getData();

        if (count($data) == 1) {
            return $data[0];
        } else {
            throw new Exception(self::ERROR_MULTIPLE_ELEMENTS_IN_RESULT);
        }
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function mutateData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return in_array($this->getStatusCode(), self::ALL_STATUS_CODE_SUCCESS) == false;
    }
}