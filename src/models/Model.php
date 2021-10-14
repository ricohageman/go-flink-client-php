<?php

namespace GoFlink\Client\Models;

use GoFlink\Client\Exception;
use GoFlink\Client\Response;

abstract class Model
{
    /**
     * Error constants.
     */
    protected const ERROR_CANNOT_CREATE_MODEL_FROM_FAILED_REQUEST = "Cannot create a model from a failed request.";
    protected const ERROR_CANNOT_CREATE_MODEL_FROM_EMPTY_RESULT = "Cannot create a model from an empty response.";
    protected const ERROR_CANNOT_CREATE_MODEL_DUE_TO_MISSING_ELEMENT = "Cannot create a model from this request due to a missing key: '%s'.";

    /**
     * @var array
     */
    protected array $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param Response $response
     * @param string[] $allRequiredElementDataKey
     */
    protected static function assertCanCreateFromResponse(Response $response, array $allRequiredElementDataKey): void
    {
        self::assertResponseIsSuccessful($response);
        self::assertMinimumNumberOfElementsPresent($response);
        self::assertAllRequiredElementDataKeysArePresent($response, $allRequiredElementDataKey);
    }

    /**
     * @param Response $response
     *
     * @throws Exception
     */
    private static function assertResponseIsSuccessful(Response $response): void
    {
        if ($response->isError()) {
            throw new Exception(self::ERROR_CANNOT_CREATE_MODEL_FROM_FAILED_REQUEST);
        } else {
            // Response is successful, continue.
        }
    }

    private static function assertMinimumNumberOfElementsPresent(Response $response): void
    {
        if (count($response->getData()) >= 1) {
            // There is at least one element present, continue.
        } else {
            throw new Exception(self::ERROR_CANNOT_CREATE_MODEL_FROM_EMPTY_RESULT);
        }
    }

    /**
     * @param Response $response
     * @param string[] $allRequiredElementDataKey
     */
    private static function assertAllRequiredElementDataKeysArePresent(
        Response $response,
        array $allRequiredElementDataKey
    ): void {
        $data = $response->getData();

        foreach ($data as $object) {
            self::assertAllRequiredElementDataKeysArePresentInData($object, $allRequiredElementDataKey);
        }
    }

    /**
     * @param array $data
     * @param string[] $allRequiredElementDataKey
     */
    private static function assertAllRequiredElementDataKeysArePresentInData(
        array $data,
        array $allRequiredElementDataKey
    ): void {
        foreach ($allRequiredElementDataKey as $requiredElementDataKey) {
            self::assertRequiredElementDataKeyIsPresent($data, $requiredElementDataKey);
        }
    }

    /**
     * @param array $data
     * @param string $dataKey
     *
     * @throws Exception
     */
    private static function assertRequiredElementDataKeyIsPresent(array $data, string $dataKey): void
    {
        if (isset($data[$dataKey])) {
            // Data key is present, continue.
        } else {
            throw new Exception(
                vsprintf(
                    self::ERROR_CANNOT_CREATE_MODEL_DUE_TO_MISSING_ELEMENT,
                    [$dataKey],
                )
            );
        }

    }
}