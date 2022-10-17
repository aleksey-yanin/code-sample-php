<?php

namespace Native\ApiClient\Endpoints;

use Native\ApiClient\Helpers\AuthType;
use Native\ApiClient\Interfaces\Internal\EndpointInterface;
use Native\ApiClient\Results\AbstractResult;

/**
 * Class AbstractEndpoint
 *
 * @package Native\ApiClient\Endpoints
 */
abstract class AbstractEndpoint implements EndpointInterface
{
    
    // output types
    const API_OUTPUT_FORMAT_UNDEFINED = 'none';
    
    const API_OUTPUT_FORMAT_XML = 'xml';
    
    const API_OUTPUT_FORMAT_PHP = 'php';
    
    const API_OUTPUT_FORMAT_JSON = 'json';
    
    // request methods
    const HTTP_REQUEST_METHOD_GET = 'GET';
    
    const HTTP_REQUEST_METHOD_POST = 'POST';
    
    
    protected $url;
    
    protected $requestMethod = self::HTTP_REQUEST_METHOD_GET;
    
    protected $outputFormat = self::API_OUTPUT_FORMAT_JSON;
    
    protected $authType = AuthType::AUTH_TYPE_NONE;
    
    protected $params = [];
    
    
    /**
     * @param string $apiResponseString
     *
     * @return array
     * @throws \Exception
     */
    public function parseResponse($apiResponseString)
    {
        switch ($this->outputFormat) {
            case self::API_OUTPUT_FORMAT_UNDEFINED:
                $parsedResponseAsAssoc = [];
                break;
            case self::API_OUTPUT_FORMAT_XML:
                $parsedResponseAsAssoc = $this->parseXmlResult($apiResponseString);
                break;
            case self::API_OUTPUT_FORMAT_PHP:
                throw new \Exception("[AbstractEndpoint] PHP serialized results are not supported because of possible security issues with `unserialize()`", AbstractResult::ERROR_RESULT);
                break;
            case self::API_OUTPUT_FORMAT_JSON:
                $parsedResponseAsAssoc = $this->parseJsonResult($apiResponseString);
                break;
            default:
                throw new \Exception("[AbstractEndpoint] not able to parse the `{$this->outputFormat}` output format", AbstractResult::ERROR_RESULT);
        }
        
        return $parsedResponseAsAssoc;
    }
    
    /**
     * @param $apiResponseString
     *
     * @return array
     */
    protected function parseXmlResult($apiResponseString)
    {
        // TODO parse XML and return as assoc array
        throw new \Exception("[AbstractEndpoint] XML response parsing is not implemented yet", AbstractResult::ERROR_RESULT);
        
        return [];
    }
    
    /**
     * @param $apiResponseString
     *
     * @return array
     */
    protected function parseJsonResult($apiResponseString)
    {
        if (0 === mb_strpos($apiResponseString, 'loaded')) {
            $apiResponseString = mb_substr($apiResponseString, 7, mb_strlen($apiResponseString) - 8);
        }
        $decoded = json_decode($apiResponseString, true);
        
        return !is_null($decoded) ? $decoded : [];
    }
    
    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }
    
    /**
     * @return int
     */
    public function getAuthType()
    {
        return $this->authType;
    }
    
    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
    
    protected function addParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }
}