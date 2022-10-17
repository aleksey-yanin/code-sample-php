<?php

namespace Native\ApiClient\Endpoints;

use Native\ApiClient\Helpers\AuthType;

/**
 * Class RefreshToken
 *
 * @package Native\ApiClient\Endpoints
 */
class RefreshToken extends AbstractEndpoint
{
    
    protected $url = "https://api.some-auction-service.com/yconnect/v2/token";
    
    protected $requestMethod = self::HTTP_REQUEST_METHOD_POST;
    
    protected $outputFormat = self::API_OUTPUT_FORMAT_JSON;
    
    protected $authType = AuthType::AUTH_TYPE_BASIC;
    
    /**
     * RefreshToken constructor.
     *
     * @param       $refreshToken
     * @param array $params
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($refreshToken, array $params = [])
    {
        $this->addParams([
                             'grant_type'    => 'refresh_token',
                             'refresh_token' => $refreshToken,
                         ]);
        $this->addParams($params);
    }
    
}