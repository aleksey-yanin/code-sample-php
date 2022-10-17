<?php

namespace Native\ApiClient\Interfaces\Internal;

/**
 * Interface EndpointInterface
 *
 * @package Native\ApiClient\Interfaces\Internal
 */
interface EndpointInterface
{
    
    /**
     * @return string
     */
    public function getUrl();
    
    /**
     * @return string
     */
    public function getRequestMethod();
    
    /**
     * @return array
     */
    public function getParams();
    
    /**
     * @return int
     */
    public function getAuthType();
    
    /**
     * @param string $apiResponseString
     *
     * @return array
     */
    public function parseResponse($apiResponseString);
}