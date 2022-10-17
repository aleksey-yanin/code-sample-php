<?php

namespace Native\ApiClient\Interfaces\External;


use Native\ApiClient\Interfaces\Internal\EndpointInterface;

/**
 * Interface AuthInterface
 *
 * @package Native\ApiClient\Interfaces\External
 */
interface AuthInterface
{
    
    /**
     * Creates an instance
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $login
     * @param string $password
     * @param string $webdriverHost
     * @param string $sourceOAuthRedirectUri
     * @param array  $options
     *
     * @return self
     */
    public static function create(
        $clientId, // required to request public API and acquire advanced tokens
        $clientSecret = null, // required to refresh access token
        $login = null, // required to get tokens by full YConnect auth procedure
        $password = null, // required to get tokens by full YConnect auth procedure
        $webdriverHost = null, // required to get tokens by full YConnect auth procedure
        $sourceOAuthRedirectUri = null, // required to get tokens by full YConnect auth procedure
        array $options = []
    );
    
    /**
     * Changes identity
     *
     * @param $login
     * @param $password
     *
     * @return void
     */
    public function changeLogin($login, $password);
    
    /**
     * Sets a method to load OAuth credentials to use in API Client
     *
     * Callable MUST return an associative array:
     *   ['accessToken' => string, 'refreshToken' => string]
     *
     * Example:
     *   function () {
     *      // project-specific storage logic here
     *      return [
     *        'accessToken' => 'access_token_from_storage',
     *        'refreshToken' => 'refresh_token_from_storage',
     *      ];
     *   }
     *
     * Callable MUST catch all of its own exceptions
     *
     * @param callable $loadMethod
     *
     * @return void
     */
    public function setLoadMethod(callable $loadMethod);
    
    /**
     * Sets a method to store OAuth credentials used to access private API endpoints
     *
     * Callable MUST accept two arguments as follows:
     *   function ($accessToken, $refreshToken) {
     *      // project-specific storage logic here
     *   }
     *
     * Callable MUST catch all of its own exceptions
     *
     * @param callable $persistMethod
     *
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function setPersistMethod(callable $persistMethod);
    
    /**
     * Performs request to a specified endpoint
     *
     * @param \Native\ApiClient\Interfaces\Internal\EndpointInterface $endpoint
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function makeRequest(EndpointInterface $endpoint);
    
    /**
     * Should be called when regular request fails
     *
     * @param \Native\ApiClient\Interfaces\Internal\EndpointInterface $endpoint
     * @param int                                                     $triesCount
     *
     * @return void
     */
    public function authFailed(EndpointInterface $endpoint, $triesCount = 0);
    
    /**
     * Performs 'yconnect' OAuth procedure to acquire access and refresh tokens
     *
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function yconnect();
    
    /**
     * Acquires access token by authorization code
     *
     * @param       $authorizationCode
     * @param array $params
     *
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function acquireTokenByCode($authorizationCode, array $params = []);
    
    /**
     * Performs refresh operation for access token using refresh token
     *
     * @param array $params
     *
     * @return bool
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function refresh(array $params = []);
    
    /**
     * Sets array of options
     *
     * @param array $options
     */
    public function setOptions(array $options);
}