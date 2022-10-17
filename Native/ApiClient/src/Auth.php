<?php

namespace Native\ApiClient;


use GuzzleHttp\Psr7\Uri;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\Exception;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\StreamFactory\GuzzleStreamFactory;
use Native\ApiClient\Auth\SourceAppId;
use Native\ApiClient\Auth\SourceLogin;
use Native\ApiClient\Endpoints\AbstractEndpoint;
use Native\ApiClient\Endpoints\AcquireToken;
use Native\ApiClient\Endpoints\Authorization;
use Native\ApiClient\Endpoints\RefreshToken;
use Native\ApiClient\Exceptions\AuthException;
use Native\ApiClient\Exceptions\CommonException;
use Native\ApiClient\Helpers\AuthType;
use Native\ApiClient\Helpers\CsrfTokenGenerator;
use Native\ApiClient\Interfaces\External\AuthInterface;
use Native\ApiClient\Interfaces\Internal\EndpointInterface;
use Native\ApiClient\Results\AcquireTokenResult;
use Native\ApiClient\Results\RefreshTokenResult;

/**
 * Class Auth
 *
 * @package Native\ApiClient
 */
class Auth implements AuthInterface
{
    
    
    const CSRF_TOKEN_LENGTH = 32;
    
    protected $clientId;
    
    protected $clientSecret;
    
    protected $login;
    
    protected $password;
    
    protected $webdriverHost;
    
    protected $redirectUri;
    
    protected $options = [];
    
    
    protected $loadMethod;
    
    protected $persistMethod;
    
    
    protected $accessToken = '';
    
    protected $refreshToken = '';
    
    
    protected $httpClient;
    
    protected $messageFactory;
    
    protected $streamFactory;
    
    protected $csrfTokenGenerator;
    
    
    /**
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $login
     * @param string $password
     * @param string $webdriverHost
     * @param string $sourceOAuthRedirectUri
     * @param array  $options
     *
     */
    protected function __construct(
        $clientId,
        $clientSecret = null,
        $login = null,
        $password = null,
        $webdriverHost = null,
        $sourceOAuthRedirectUri = null,
        $options = []
    ) {
        $this->clientId      = $clientId;
        $this->clientSecret  = $clientSecret;
        $this->login         = $login;
        $this->password      = $password;
        $this->webdriverHost = $webdriverHost;
        $this->redirectUri   = $sourceOAuthRedirectUri;
        $this->options       = $options;
        
        
        $httpClientOptions = [
            CURLOPT_FORBID_REUSE  => true,
            CURLOPT_FRESH_CONNECT => true,
        ];
        
        if (isset($this->options['proxy'])) {
            $httpClientOptions[CURLOPT_PROXY] = $this->options['proxy'];
        }
        
        if (isset($this->options['timeout'])) {
            $httpClientOptions[CURLOPT_TIMEOUT] = (int)$this->options['timeout'];
        }
        
        if (isset($this->options['connectionTimeout'])) {
            $httpClientOptions[CURLOPT_CONNECTTIMEOUT] = (int)$this->options['connectionTimeout'];
        }
        
        $this->messageFactory     = new GuzzleMessageFactory();
        $this->streamFactory      = new GuzzleStreamFactory();
        $this->httpClient         = new \Http\Client\Curl\Client($this->messageFactory, $this->streamFactory, $httpClientOptions);
        $this->csrfTokenGenerator = new CsrfTokenGenerator();
    }
    
    /**
     * @param $login
     * @param $password
     */
    public function changeLogin($login, $password)
    {
        $this->login    = $login;
        $this->password = $password;
        $this->invalidateAccessToken();
        $this->invalidateRefreshToken();
        
        $this->loadCredentials();
    }
    
    /**
     *
     */
    protected function invalidateAccessToken()
    {
        $this->accessToken = '';
    }
    
    /**
     *
     */
    protected function invalidateRefreshToken()
    {
        $this->refreshToken = '';
    }
    
    /**
     * @inheritdoc
     */
    protected function loadCredentials()
    {
        if (is_callable($this->loadMethod)) {
            $credentials = call_user_func($this->loadMethod);
            
            if (isset($credentials['accessToken'])) {
                $this->accessToken = $credentials['accessToken'];
            }
            
            if (isset($credentials['refreshToken'])) {
                $this->refreshToken = $credentials['refreshToken'];
            }
        }
    }
    
    /**
     * @param \Native\ApiClient\Interfaces\Internal\EndpointInterface $endpoint
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function makeRequest(EndpointInterface $endpoint)
    {
        try {
            return $this->makeAuthorizedRequest($endpoint);
        } catch (AuthException $e) {
            throw new AuthException("[Auth::makeRequest] {$e->getMessage()}");
        } catch (CommonException $e) {
            throw new AuthException("[Auth::makeRequest] common client error: {$e->getMessage()}");
        } catch (\Exception $e) {
            throw new AuthException("[Auth::makeRequest] error: {$e->getMessage()}");
        }
    }
    
    /**
     * @param \Native\ApiClient\Interfaces\Internal\EndpointInterface $endpoint
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    protected function makeAuthorizedRequest(EndpointInterface $endpoint)
    {
        switch ($endpoint->getAuthType()) {
            case AuthType::AUTH_TYPE_BASIC:
                $this->armClientId();
                $this->armClientSecret();
                $authentication = new BasicAuth($this->clientId, $this->clientSecret);
                break;
            case AuthType::AUTH_TYPE_CLIENT_ID:
                $this->armClientId();
                $authentication = new SourceAppId($this->clientId);
                break;
            case AuthType::AUTH_TYPE_OAUTH:
                $this->armAccessToken();
                $authentication = new Bearer($this->accessToken);
                break;
            default:
                $authentication = null;
        }
        
        $plugins = [];
        if (!empty($authentication)) {
            $plugins[] = new AuthenticationPlugin($authentication);
        }
        
        $httpClient = new PluginClient(
            $this->httpClient,
            $plugins
        );
        
        try {
            switch ($endpoint->getRequestMethod()) {
                case AbstractEndpoint::HTTP_REQUEST_METHOD_GET:
                    $requestMethod = 'GET';
                    $uri           = new Uri($endpoint->getUrl());
                    $uri           = $uri->withQuery(http_build_query($endpoint->getParams()));
                    $body          = '';
                    break;
                case AbstractEndpoint::HTTP_REQUEST_METHOD_POST:
                    $requestMethod = 'POST';
                    $uri           = $endpoint->getUrl();
                    $body          = http_build_query($endpoint->getParams());
                    break;
                default:
                    throw new AuthException("request method {$endpoint->getRequestMethod()} not supported for endpoint " . get_class($endpoint));
            }
            
            $request = $this->messageFactory->createRequest($requestMethod, $uri, [
                'Connection' => 'close',
            ],                                              $body);
            
            return $httpClient->sendRequest($request);
        } catch (\Http\Client\Exception $e) {
            // отлавливаем ошибки коннекта
            throw new AuthException("connection problem while requesting " . get_class($endpoint) . " endpoint: {$e->getMessage()}, code {$e->getCode()}");
        } catch (\Exception $e) {
            // ловим прочие ошибки
            throw new AuthException("impossible to request " . get_class($endpoint) . " endpoint: {$e->getMessage()}, code {$e->getCode()}");
        }
    }
    
    /**
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    protected function armClientId()
    {
        if (!$this->validateClientId()) {
            throw new AuthException("ClientId is not valid");
        }
    }
    
    /**
     * @return bool
     */
    protected function validateClientId()
    {
        if (empty($this->clientId)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    protected function armClientSecret()
    {
        if (!$this->validateClientSecret()) {
            throw new AuthException("Client secret is not valid");
        }
    }
    
    /**
     * @return bool
     */
    protected function validateClientSecret()
    {
        if (empty($this->clientSecret)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @param int $power
     *
     * @throws \Native\ApiClient\Exceptions\AuthException
     * @throws \Exception
     */
    protected function armAccessToken($power = 0)
    {
        while (!$this->validateAccessToken()) {
            switch ($power) {
                case 0: // load
                    $this->loadCredentials();
                    break;
                case 1: // refresh
                    try {
                        $this->refreshToken();
                    } catch (AuthException $e) {
                        // nothing here since it's not a fatal error
                    }
                    break;
                case 2: // new auth
                    $this->makeYConnect();
                    break;
                default: // pain and despair
                    throw new AuthException("unable to arm access token with power = {$power}");
            }
            $power++;
        }
    }
    
    /**
     * @return bool
     */
    protected function validateAccessToken()
    {
        if (empty($this->accessToken)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @param array $params
     *
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     * @throws \Exception
     */
    protected function refreshToken(array $params = [])
    {
        if (!$this->validateRefreshToken()) {
            throw new AuthException("refresh token is not valid");
        }
        
        $endpoint = new RefreshToken($this->refreshToken, $params);
        
        $response = $this->makeAuthorizedRequest($endpoint);
        
        $apiResponseString = $response->getBody()->getContents();
        
        $responseArray = $endpoint->parseResponse($apiResponseString);
        
        if (!is_array($responseArray)) {
            throw new AuthException("[refreshToken] cannot parse response");
        }
        
        $result = new RefreshTokenResult();
        $result->set($responseArray);
        
        switch ($response->getStatusCode()) {
            case 200:
                $this->accessToken = $result->accessToken;
                $this->persistCredentials();
                break;
            
            case 401:
                $this->invalidateRefreshToken();
                break;
            
            default:
                throw new AuthException("response returned with {$response->getStatusCode()} {$response->getReasonPhrase()} (expected 200 OK), error: {$result->getSourceErrorMessage()}");
        }
    }
    
    /**
     * @return bool
     */
    protected function validateRefreshToken()
    {
        if (empty($this->refreshToken)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @inheritdoc
     */
    protected function persistCredentials()
    {
        if (is_callable($this->persistMethod)) {
            call_user_func($this->persistMethod, $this->accessToken, $this->refreshToken);
        }
    }
    
    /**
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     * @throws \Exception
     */
    protected function makeYConnect()
    {
        if (empty($this->login)) {
            throw new AuthException("empty login");
        }
        
        if (empty($this->password)) {
            throw new AuthException("empty password");
        }
        
        $csrfToken = $this->csrfTokenGenerator->generate(static::CSRF_TOKEN_LENGTH);
        $endpoint  = new Authorization($this->clientId, $this->redirectUri, $csrfToken);
        
        $response = $this->makeAuthorizedRequest($endpoint);
        
        if ($response->getStatusCode() !== 302) {
            throw new AuthException("authorization endpoint response is {$response->getStatusCode()} {$response->getReasonPhrase()}, expected 302 Found (redirect to login form)");
        }
        
        // get redirected to login form
        $oauthLoginUrl = $response->getHeaderLine('location');
        
        try {
            if (empty($oauthLoginUrl)) {
                throw new AuthException("got empty OAuth login URL");
            }
            
            if (false !== mb_strpos($oauthLoginUrl, $this->redirectUri)) { // redirected back with error
                parse_str(parse_url($oauthLoginUrl)['query'], $errorQueryParams);
                
                if (isset($errorQueryParams['error_description'])) {
                    $errorMessage = urldecode($errorQueryParams['error_description']);
                } else {
                    $errorMessage = "Unknown error";
                }
                
                if (isset($errorQueryParams['error_code'])) {
                    $errorMessage .= " (code {$errorQueryParams['error_code']})";
                }
                
                throw new AuthException("authorization endpoint returned error: {$errorMessage}");
            }
            
            // login to source OAuth service
            $sourceLogin   = SourceLogin::create($this->webdriverHost, $this->getOption('webdriverOptions'));
            $finalLoginUrl = $sourceLogin->login($oauthLoginUrl, $this->login, $this->password);
            
            if (
                empty($finalLoginUrl)
                || !is_string($finalLoginUrl)
                || false === mb_strpos($finalLoginUrl, $this->redirectUri)
            ) {
                throw new AuthException("got invalid final URL: '$finalLoginUrl', expected '{$this->redirectUri}'");
            }
            
            // parse final redirect url after login and extract the authorization code
            parse_str(parse_url($finalLoginUrl)['query'], $redirectedQueryParams);
            
            if (!isset($redirectedQueryParams['code'])) {
                throw new AuthException("cannot extract authorization code from '$finalLoginUrl'");
            }
            
            if (!isset($redirectedQueryParams['state']) || $redirectedQueryParams['state'] != $csrfToken) {
                throw new AuthException("CSRF token $csrfToken mismatching with {$redirectedQueryParams['state']}");
            }
            
            $authorizationCode = $redirectedQueryParams['code'];
        } catch (AuthException $e) {
            throw new AuthException("OAuth login failed: {$e->getMessage()}");
        };
        
        // acquire tokens with auth code
        $this->acquireTokens($authorizationCode);
    }
    
    /**
     * Creates an instance
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $login
     * @param string $password
     * @param string $webdriverHost
     * @param string $sourceOAuthReturnUrl
     * @param array  $options
     *
     * @return \Native\ApiClient\Auth
     */
    public static function create(
        $clientId, // required to request public API and acquire advanced tokens
        $clientSecret = null, // required to get tokens
        $login = null, // required to get tokens by full YConnect auth procedure
        $password = null, // required to get tokens by full YConnect auth procedure
        $webdriverHost = null, // required to get tokens by full YConnect auth procedure
        $sourceOAuthReturnUrl = null, // required to get tokens by full YConnect auth procedure
        array $options = []
    ) {
        return new static(
            $clientId,
            $clientSecret,
            $login,
            $password,
            $webdriverHost,
            $sourceOAuthReturnUrl,
            $options
        );
    }
    
    /**
     * @param $name
     *
     * @return mixed|null
     */
    protected function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }
    
    /**
     * @param       $authorizationCode
     * @param array $params
     *
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     * @throws \Exception
     */
    protected function acquireTokens($authorizationCode, array $params = [])
    {
        $endpoint = new AcquireToken($authorizationCode, $this->redirectUri, $params);
        
        $response = $this->makeAuthorizedRequest($endpoint);
        
        $apiResponseString = $response->getBody()->getContents();
        
        $responseArray = $endpoint->parseResponse($apiResponseString);
        
        if (!is_array($responseArray)) {
            throw new AuthException("[acquireAccessToken] cannot parse response");
        }
        
        $result = new AcquireTokenResult();
        $result->set($responseArray);
        
        switch ($response->getStatusCode()) {
            case 200:
                $this->accessToken  = $result->accessToken;
                $this->refreshToken = $result->refreshToken;
                
                $this->persistCredentials();
                break;
            default:
                throw new AuthException("got response: {$response->getStatusCode()} {$response->getReasonPhrase()} (expected 200 OK), error: {$result->getSourceErrorMessage()}");
        }
    }
    
    /**
     * @param \Native\ApiClient\Interfaces\Internal\EndpointInterface $endpoint
     * @param int                                                     $triesCount
     *
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function authFailed(EndpointInterface $endpoint, $triesCount = 0)
    {
        switch ($endpoint->getAuthType()) {
            case AuthType::AUTH_TYPE_BASIC:
                throw new AuthException("Unauthorized request to " . get_class($endpoint) . " endpoint (basic auth). Provide correct clientId and secret.");
                break;
            case AuthType::AUTH_TYPE_CLIENT_ID:
                throw new AuthException("Unauthorized request to " . get_class($endpoint) . " endpoint (client_id auth). Provide correct clientId.");
                break;
            case AuthType::AUTH_TYPE_OAUTH:
                if ($this->validateAccessToken() && $triesCount == 0) {
                    // if we have tried with valid access token and got auth failed, loading credentials makes no sense
                    $triesCount++;
                }
                $this->invalidateAccessToken();
                $this->armAccessToken($triesCount);
                break;
            default:
        }
    }
    
    /**
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function yconnect()
    {
        try {
            $this->makeYConnect();
        } catch (AuthException $e) {
            throw new AuthException("[Auth::yconnect] {$e->getMessage()}, used login: $this->login", 0, $e);
        } catch (\Exception $e) {
            throw new AuthException("[Auth::yconnect] error: {$e->getMessage()}, used login: $this->login", 0, $e);
        }
    }
    
    /**
     * @param array $params
     *
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function refresh(array $params = [])
    {
        try {
            $this->armRefreshToken();
            $this->refreshToken($params);
        } catch (AuthException $e) {
            throw new AuthException("[Auth::refresh] {$e->getMessage()}, used login: $this->login", 0, $e);
        } catch (\Exception $e) {
            throw new AuthException("[Auth::refresh] error: {$e->getMessage()}, used login: $this->login", 0, $e);
        }
    }
    
    /**
     * @param int $power
     *
     * @throws \Native\ApiClient\Exceptions\AuthException
     * @throws \Exception
     */
    protected function armRefreshToken($power = 0)
    {
        while (!$this->validateRefreshToken()) {
            switch ($power) {
                case 0: // load
                    $this->loadCredentials();
                    break;
                
                default: // pain and despair
                    throw new AuthException("unable to arm refresh token with power = {$power}");
            }
            $power++;
        }
    }
    
    /**
     * @param       $authorizationCode
     * @param array $params
     *
     * @return void
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function acquireTokenByCode($authorizationCode, array $params = [])
    {
        try {
            $this->acquireTokens($authorizationCode, $params);
        } catch (AuthException $e) {
            throw new AuthException("[Auth::acquireByCode] {$e->getMessage()}, used login: $this->login", 0, $e);
        } catch (\Exception $e) {
            throw new AuthException("[Auth::acquireByCode] error: {$e->getMessage()}, used login: $this->login", 0, $e);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function setLoadMethod(callable $loadMethod)
    {
        $this->loadMethod = $loadMethod;
    }
    
    /**
     * @inheritdoc
     */
    public function setPersistMethod(callable $persistMethod)
    {
        $this->persistMethod = $persistMethod;
    }
    
    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }
}