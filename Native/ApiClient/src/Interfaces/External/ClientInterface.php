<?php

namespace Native\ApiClient\Interfaces\External;

/**
 * Main API Client Interface
 *
 * @package Native\ApiClient\Interfaces\External
 */
interface ClientInterface
{
    
    /**
     * Returns a Client instance
     *
     * @param \Native\ApiClient\Interfaces\External\AuthInterface $auth
     * @param array                                               $options
     *
     * @return \Native\ApiClient\Interfaces\External\ClientInterface
     */
    public static function create(AuthInterface $auth, array $options = []);
    
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
     * Performs search by query string and optional parameters
     *
     * @param       $queryString
     * @param int   $auctionsOnPage
     * @param int   $pageNumber
     * @param array $params
     *
     * @return \Native\ApiClient\Interfaces\External\ResultInterface
     */
    public function search($queryString, $auctionsOnPage = 0, $pageNumber = 1, array $params = []);
}