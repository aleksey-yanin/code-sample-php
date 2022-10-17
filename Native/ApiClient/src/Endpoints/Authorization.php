<?php

namespace Native\ApiClient\Endpoints;

use Native\ApiClient\Helpers\AuthType;

/**
 * Class Authorization
 *
 * @package Native\ApiClient\Endpoints
 */
class Authorization extends AbstractEndpoint
{
    
    protected $url = "https://api.some-auction-service.com/yconnect/v2/authorization";
    
    protected $requestMethod = self::HTTP_REQUEST_METHOD_GET;
    
    protected $outputFormat = self::API_OUTPUT_FORMAT_UNDEFINED;
    
    protected $authType = AuthType::AUTH_TYPE_NONE;
    
    
    public function __construct($client_id, $redirectUri, $csrfToken = null, array $params = [])
    {
        $this->addParams([
                             'response_type' => 'code',
                             'client_id'     => $client_id,
                             'redirect_uri'  => $redirectUri,
                             'bail'          => 1,
                             'scope'         => 'openid',
                             'state'         => $csrfToken,
                         ]);
        $this->addParams($params);
    }
    
    public function getUrl()
    {
        return $this->url . '?' . http_build_query($this->getParams());
    }
}