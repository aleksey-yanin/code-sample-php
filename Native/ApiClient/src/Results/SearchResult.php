<?php

namespace Native\ApiClient\Results;


use Native\ApiClient\Results\Items\SearchItem;

/**
 * Class SearchResult
 *
 * @package Native\ApiClient\Results
 */
class SearchResult extends AbstractResult
{
    
    /**
     * @var int
     */
    public $totalResultsAvailable;
    
    /**
     * @var int
     */
    public $totalResultsReturned;
    
    /**
     * @var int
     */
    public $firstResultPosition;
    
    /**
     * @var array
     */
    public $words = [];
    
    /**
     * @var array
     */
    public $items = [];
    
    /**
     * @param array $response
     *
     * @return void
     * @throws \Exception
     */
    public function mapValues(array $response)
    {
        if (!isset($response['ResultSet'])) {
            return;
        }
        
        $response = $response['ResultSet'];
        
        $this->totalResultsAvailable = (int)$response['@attributes']['totalResultsAvailable'];
        $this->totalResultsReturned  = (int)$response['@attributes']['totalResultsReturned'];
        $this->firstResultPosition   = (int)$response['@attributes']['firstResultPosition'];
        
        if (isset($response['Result']['UnitsWord'])) {
            $this->words = (array)$response['Result']['UnitsWord'];
        }
        
        if (isset($response['Result']['Item']) && !empty($response['Result']['Item'])) {
            foreach ($response['Result']['Item'] as $item) {
                $itemObj = new SearchItem();
                
                $itemObj->auctionId      = $item['AuctionID'];
                $itemObj->title          = $item['Title'];
                $itemObj->categoryId     = (int)$item['CategoryId'];
                $itemObj->sellerId       = $item['Seller']['Id'];
                $itemObj->auctionUrl     = $item['AuctionItemUrl'];
                $itemObj->imageUrl       = $item['Image'];
                $itemObj->currentPrice   = (float)$item['CurrentPrice'];
                $itemObj->bidCount       = (int)$item['Bids'];
                $itemObj->endTime        = new \DateTime($item['EndTime']);
                $itemObj->hasHiddenPrice = filter_var($item['IsReserved'], FILTER_VALIDATE_BOOLEAN);
                
                if (isset($item['BidOrBuy'])) {
                    $itemObj->hasBuyPrice = true;
                    $itemObj->buyPrice    = (float)$item['BidOrBuy'];
                }
                
                $itemObj->isCharity         = filter_var($item['Option']['IsCharity'], FILTER_VALIDATE_BOOLEAN);
                $itemObj->isOffer           = filter_var($item['Option']['IsOffer'], FILTER_VALIDATE_BOOLEAN);
                $itemObj->charityProportion = isset($item['CharityOption']['Proportion']) ? (float)$item['CharityOption']['Proportion'] : 0.0;
                
                $itemObj->isAdult = filter_var($item['IsAdult'], FILTER_VALIDATE_BOOLEAN);
                
                $this->items[] = $itemObj;
            }
        }
    }
    
}