<?php

namespace Native\ApiClient\Endpoints;


use Native\ApiClient\Helpers\AuthType;

/**
 * Class AcquireToken
 *
 * @package Native\ApiClient\Endpoints
 */
class AcquireToken extends AbstractEndpoint
{
    
    protected $url = "https://api.some-auction-service.com/auth/v1/token";
    
    protected $requestMethod = self::HTTP_REQUEST_METHOD_POST;
    
    protected $outputFormat = self::API_OUTPUT_FORMAT_JSON;
    
    protected $authType = AuthType::AUTH_TYPE_BASIC;
    
    /**
     * AcquireToken constructor.
     *
     * @param       $authorizationCode
     * @param       $redirectUri
     * @param array $params
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($authorizationCode, $redirectUri, array $params = [])
    {
        if (empty($authorizationCode) || !is_string($authorizationCode)) {
            throw new \InvalidArgumentException("invalid authorization code");
        }
        
        if (empty($redirectUri) || !is_string($redirectUri)) {
            throw new \InvalidArgumentException("invalid redirect URI");
        }
        
        $this->addParams(
            [
                'grant_type'   => 'authorization_code',
                'redirect_uri' => $redirectUri,
                'code'         => $authorizationCode,
            ]
        );
        $this->addParams($params);
    }
}