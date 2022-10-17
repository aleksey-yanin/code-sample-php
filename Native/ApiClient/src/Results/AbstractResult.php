<?php

namespace Native\ApiClient\Results;

use Native\ApiClient\Helpers\SourceError;
use Native\ApiClient\Interfaces\External;


/**
 * Abstract parent class for all Results.
 * Don't implement a constructor here since all Result should have a specific constructor signature
 *
 * Class AbstractResult
 *
 * @package Native\ApiClient\Results
 */
abstract class AbstractResult implements External\ResultInterface
{
    
    const ERROR_NONE = 0;
    
    const ERROR_EMPTY_RESULT = 100;
    
    const ERROR_INPUT = 200;
    
    const ERROR_AUTH = 300;
    
    const ERROR_CONNECTION = 400;
    
    const ERROR_SOURCE = 500;
    
    const ERROR_RESULT = 600;
    
    const ERROR_OTHER = 1000;
    
    /**
     * @var int
     */
    protected $errorCode = self::ERROR_EMPTY_RESULT;
    
    /**
     * @var string
     */
    protected $errorMessage = '';
    
    /**
     * @var int
     */
    protected $sourceErrorCode = 0;
    
    /**
     * @var string
     */
    protected $sourceErrorMessage = '';
    
    /**
     * @var int
     */
    protected $timestamp;
    
    /**
     * @var string
     */
    protected $rawResponse = '';
    
    
    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->errorCode === self::ERROR_NONE;
    }
    
    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getErrorCode() === self::ERROR_EMPTY_RESULT;
    }
    
    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
    
    /**
     * @inheritdoc
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
    
    /**
     * @inheritdoc
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
        
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
    
    /**
     * @param array $response
     *
     * @return $this
     */
    public function set(array $response)
    {
        if ($this->isErrorResponse($response)) {
            $this->mapError($response);
        } else {
            $this->setSuccess();
        }
        
        $this->mapValues($response);
        
        return $this;
    }
    
    /**
     * @param array $response
     *
     * @return bool
     */
    protected function isErrorResponse(array $response)
    {
        return isset($response['Error']);
    }
    
    /**
     * @param array $response
     */
    protected function mapError(array $response)
    {
        $this->sourceErrorCode    = isset($response['Error']['Code']) ? (int)$response['Error']['Code'] : null;
        $this->sourceErrorMessage = isset($response['Error']['Message']) && !empty($response['Error']['Message']) ? $response['Error']['Message'] : null;
        
        if (!empty($this->sourceErrorCode)) {
            $sourceErrorMessage = SourceError::getByCode($this->sourceErrorCode);
        }
        
        if (empty($sourceErrorMessage)) {
            $sourceErrorMessage = $this->sourceErrorMessage;
        }
        
        $this->setErrorCode(self::ERROR_SOURCE)->setErrorMessage("Source error: '$sourceErrorMessage'" . ($this->sourceErrorCode ? ". Source error code: $this->sourceErrorCode" : ''));
    }
    
    /**
     * @return $this
     */
    protected function setSuccess()
    {
        $this->errorCode = self::ERROR_NONE;
        $this->setErrorMessage('');
        $this->setSourceErrorCode(0);
        $this->setSourceErrorMessage('');
        
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function getSourceErrorCode()
    {
        return $this->sourceErrorCode;
    }
    
    /**
     * @inheritdoc
     */
    public function setSourceErrorCode($sourceErrorCode)
    {
        $this->sourceErrorCode = $sourceErrorCode;
        
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function getSourceErrorMessage()
    {
        return $this->sourceErrorMessage;
    }
    
    /**
     * @inheritdoc
     */
    public function setSourceErrorMessage($sourceErrorMessage)
    {
        $this->sourceErrorMessage = $sourceErrorMessage;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }
    
    /**
     * @param $rawResponse
     *
     * @return $this
     */
    public function setRawResponse($rawResponse)
    {
        $this->rawResponse = $rawResponse;
        
        return $this;
    }
}