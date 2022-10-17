<?php

namespace Native\ApiClient\Results;

/**
 * Class AcquireTokenResult
 *
 * @package Native\ApiClient\Results
 */
class AcquireTokenResult extends AbstractResult
{
    
    public $accessToken;
    
    public $tokenType;
    
    public $refreshToken;
    
    public $expiresIn;
    
    public $idToken;
    
    /**
     * @param array $response
     */
    public function mapValues(array $response)
    {
        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
        }
        
        if (isset($response['refresh_token'])) {
            $this->refreshToken = $response['refresh_token'];
        }
        
        if (isset($response['token_type'])) {
            $this->tokenType = $response['token_type'];
        }
        
        if (isset($response['expires_in'])) {
            $this->expiresIn = (int)$response['expires_in'];
        }
        
        if (isset($response['id_token'])) {
            $this->idToken = $response['id_token'];
        }
    }
}