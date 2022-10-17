<?php

namespace Native\ApiClient\Results;

/**
 * Class RefreshTokenResult
 *
 * @package Native\ApiClient\Results
 */
class RefreshTokenResult extends AbstractResult
{
    
    public $accessToken;
    
    public $tokenType;
    
    public $expiresIn;
    
    /**
     * @param array $response
     */
    public function mapValues(array $response)
    {
        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
        }
        
        if (isset($response['token_type'])) {
            $this->tokenType = $response['token_type'];
        }
        
        if (isset($response['expires_in'])) {
            $this->expiresIn = (int)$response['expires_in'];
        }
    }
}