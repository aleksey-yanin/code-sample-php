<?php

namespace Native\ApiClient\Auth;

use Http\Discovery\StreamFactoryDiscovery;
use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;

/**
 * Implements `appid` auth type
 *
 * @package Native\ApiClient\Auth
 */
class SourceAppId implements Authentication
{
    
    /**
     * @var string
     */
    private $appId = '';
    
    /**
     * @param $appId
     */
    public function __construct($appId)
    {
        $this->appId = $appId;
    }
    
    /**
     * {@inheritdoc}
     */
    public function authenticate(RequestInterface $request)
    {
        switch ($request->getMethod()) {
            case 'GET':
                $uri    = $request->getUri();
                $query  = ($uri->getQuery() ? $uri->getQuery() . '&' : '') . http_build_query(['appid' => $this->appId]);
                $newUri = $uri->withQuery($query);
                
                return $request->withUri($newUri);
                break;
            case 'POST':
                $oldBodyElements = [];
                parse_str($request->getBody()->getContents(), $oldBodyElements);
                
                $newBodyContents = http_build_query(array_merge($oldBodyElements, ['appid' => $this->appId]));
                
                $streamFactory = StreamFactoryDiscovery::find();
                $newBody       = $streamFactory->createStream($newBodyContents);
                
                return $request->withBody($newBody);
                break;
            default:
                // not supported
        }
        
        return $request;
    }
}