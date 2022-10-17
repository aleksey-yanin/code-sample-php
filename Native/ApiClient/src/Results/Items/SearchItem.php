<?php

namespace Native\ApiClient\Results\Items;

/**
 * Represents one auction in search results
 *
 * Class SearchItem
 *
 * @package Native\ApiClient\Results\Items
 */
class SearchItem
{
    
    public $auctionId;
    
    public $title;
    
    public $categoryId;
    
    public $sellerId;
    
    public $auctionUrl;
    
    public $imageUrl;
    
    public $currentPrice = 0.0;
    
    public $bidCount;
    
    public $endTime;
    
    public $hasHiddenPrice;
    
    public $hasBuyPrice = false;
    
    public $buyPrice = 0.0;
    
    public $isCharity;
    
    public $charityProportion = 0.0;
    
    public $isOffer;
    
    public $isAdult;
    
}