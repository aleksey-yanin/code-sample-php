<?php

namespace Native\ApiClient\Endpoints;

use Native\ApiClient\Helpers\AuthType;

/**
 * Class Search
 *
 * @package Native\ApiClient\Endpoints
 */
class Search extends AbstractEndpoint
{
    
    const DEFAULT_AUCTIONS_ON_PAGE = 20;
    
    protected $url = "https://api.some-auction-service.com/v1/search";
    
    protected $requestMethod = self::HTTP_REQUEST_METHOD_GET;
    
    protected $outputFormat = self::API_OUTPUT_FORMAT_JSON;
    
    protected $authType = AuthType::AUTH_TYPE_CLIENT_ID;
    
    /**
     * Search constructor.
     *
     * @param string $queryString
     * @param int    $auctionsOnPage
     * @param int    $pageNumber
     * @param array  $params
     */
    public function __construct($queryString, $auctionsOnPage = self::DEFAULT_AUCTIONS_ON_PAGE, $pageNumber = 1, array $params = [])
    {
        $this->addParams([
                             'query'   => $queryString,
                             'output'  => $this->outputFormat,
                             'results' => $auctionsOnPage,
                             'page'    => $pageNumber,
                         ]);
        $this->addParams($params);
    }
    
}