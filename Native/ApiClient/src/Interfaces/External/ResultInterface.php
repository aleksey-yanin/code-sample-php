<?php

namespace Native\ApiClient\Interfaces\External;


/**
 * Interface for all results returned by Client
 *
 * Interface ResultInterface
 *
 * @package Native\ApiClient\Interfaces\External
 */
interface ResultInterface
{
    
    /**
     * Returns true if request was successful
     *
     * @return bool
     */
    public function isSuccess();
    
    /**
     * Returns true if result is just created and empty
     *
     * @return bool
     */
    public function isEmpty();
    
    /**
     * Returns an error code in case of failure or 0 if success
     *
     * @return int
     */
    public function getErrorCode();
    
    /**
     * Sets error code
     *
     * @param $errorCode
     *
     * @return $this
     */
    public function setErrorCode($errorCode);
    
    /**
     * Returns an error message string in case of failure
     *
     * @return string
     */
    public function getErrorMessage();
    
    /**
     * Returns original source error code
     *
     * @return int
     */
    public function getSourceErrorCode();
    
    /**
     * Returns original source error message
     *
     * @return string
     */
    public function getSourceErrorMessage();
    
    /**
     * Returns raw response
     *
     * @return string
     */
    public function getRawResponse();
    
    /**
     * Sets error message
     *
     * @param $errorMessage
     *
     * @return $this
     */
    public function setErrorMessage($errorMessage);
    
    /**
     * Sets source error code
     *
     * @param $sourceErrorCode
     *
     * @return mixed
     */
    public function setSourceErrorCode($sourceErrorCode);
    
    /**
     * Sets source error message
     *
     * @param $sourceErrorMessage
     *
     * @return mixed
     */
    public function setSourceErrorMessage($sourceErrorMessage);
    
    /**
     * Sets raw response for debug purposes
     *
     * @param $rawResponse
     *
     * @return $this
     */
    public function setRawResponse($rawResponse);
    
    /**
     * Sets response (in the form of array) to result for error checking and mapping
     *
     * @param array $response
     *
     * @return $this
     */
    public function set(array $response);
    
    /**
     * Maps response array to result object's properties
     *
     * This method MUST NOT throw any exceptions since these errors are not fatal
     *
     * @param array $response
     *
     * @return void
     */
    public function mapValues(array $response);
}