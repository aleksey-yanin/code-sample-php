<?php


namespace Native\ApiClient;


use Native\ApiClient\Endpoints\Search;
use Native\ApiClient\Exceptions\AuthException;
use Native\ApiClient\Interfaces\External\AuthInterface;
use Native\ApiClient\Interfaces\External\ClientInterface;
use Native\ApiClient\Interfaces\External\ResultInterface;
use Native\ApiClient\Interfaces\Internal\EndpointInterface;
use Native\ApiClient\Results\AbstractResult;
use Native\ApiClient\Results\SearchResult;

/**
 * Class Client
 */
class Client implements ClientInterface
{
    
    /** @var AuthInterface */
    protected $auth;
    
    /**
     * Client constructor.
     *
     * @param AuthInterface $auth
     * @param array         $options
     */
    protected function __construct(AuthInterface $auth, array $options = [])
    {
        $this->auth = $auth;
        $this->auth->setOptions($options);
    }
    
    /**
     * @param AuthInterface $auth
     * @param array         $options
     *
     * @return \Native\ApiClient\Client
     */
    public static function create(AuthInterface $auth, array $options = [])
    {
        return new static($auth, $options);
    }
    
    /**
     * @param $login
     * @param $password
     */
    public function changeLogin($login, $password)
    {
        $this->auth->changeLogin($login, $password);
    }
    
    /**
     * @param string $queryString
     * @param int    $auctionsOnPage
     * @param int    $pageNumber
     * @param array  $params
     *
     * @return SearchResult
     */
    public function search($queryString, $auctionsOnPage = 0, $pageNumber = 1, array $params = [])
    {
        return $this->makeRequest(new Search($queryString, $auctionsOnPage, $pageNumber, $params), new SearchResult());
    }
    
    // много методов для доступа к соответствующим эндпоинтам стороннего API
    
    /**
     * @param \Native\ApiClient\Interfaces\Internal\EndpointInterface $endpoint
     * @param \Native\ApiClient\Interfaces\External\ResultInterface   $result
     *
     * @return \Native\ApiClient\Interfaces\External\ResultInterface
     */
    protected function makeRequest(EndpointInterface $endpoint, ResultInterface $result)
    {
        try {
            $isRetryAuth = false;
            $triesCount  = 0;
            
            do {
                $response = $this->auth->makeRequest($endpoint);
                
                $apiResponseString = $response->getBody()->getContents();
                
                $responseArray = $endpoint->parseResponse($apiResponseString);
                
                $result->set($responseArray);
                
                // intercept connection and auth errors
                switch ($response->getStatusCode()) {
                    case 200:
                        $isRetryAuth = false;
                        break;
                    
                    case 400:
                        throw new \Exception("bad request to '{$endpoint->getUrl()}", AbstractResult::ERROR_INPUT);
                        break;
                    
                    case 401:
                        $isRetryAuth = true;
                        $this->auth->authFailed($endpoint, $triesCount++);
                        break;
                    
                    case 403:
                        throw new \Exception("forbidden request to '{$endpoint->getUrl()}': access to resource not allowed or usage limit exceeded", AbstractResult::ERROR_SOURCE);
                        break;
                    
                    case 404:
                        if ($result->getErrorCode() === AbstractResult::ERROR_EMPTY_RESULT) {
                            throw new \Exception("requested API resource URL '{$endpoint->getUrl()}' not found", AbstractResult::ERROR_CONNECTION);
                        }
                        break;
                    
                    case 500:
                    case 503:
                        throw new \Exception("request to '{$endpoint->getUrl()}' failed: {$response->getStatusCode()} {$response->getReasonPhrase()}", AbstractResult::ERROR_SOURCE);
                        break;
                    
                    default:
                        throw new \Exception("request to '{$endpoint->getUrl()}' failed: {$response->getStatusCode()} {$response->getReasonPhrase()}");
                }
            } while ($isRetryAuth);
        } catch (AuthException $e) {
            $result->setErrorCode(AbstractResult::ERROR_AUTH)->setErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $code    = $e->getCode() ?: AbstractResult::ERROR_OTHER;
            $message = !empty($result->getErrorMessage()) ? "{$e->getMessage()}. {$result->getErrorMessage()}" : $e->getMessage();
            
            if (isset($apiResponseString) && !empty($apiResponseString)) {
                $result->setRawResponse($apiResponseString);
            }
            
            $result->setErrorCode($code)->setErrorMessage($message);
        }
        
        return $result;
    }
}