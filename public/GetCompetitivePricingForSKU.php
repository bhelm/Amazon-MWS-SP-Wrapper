<?php

use AmazonMWSSPWrapper\AmazonSP\RequestParams;
use AmazonPHP\SellingPartner\Model\ProductPricing\CustomerType;
use AmazonPHP\SellingPartner\Model\ProductPricing\GetListingOffersBatchRequest;
use AmazonPHP\SellingPartner\Model\ProductPricing\HttpMethod;
use AmazonPHP\SellingPartner\Model\ProductPricing\ItemCondition;
use AmazonPHP\SellingPartner\Model\ProductPricing\ListingOffersRequest;
use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';

$sdk = SdkConfig::getSdk();
$logger = new DebugLogger();
$accessToken = SdkConfig::getAccessToken();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($queryString, $queryParams);
$sellerId = $queryParams['SellerId'];

$skus = RequestParams::extractMultipleValues('SellerSKUList_SellerSKU');
$marketplaceId = RequestParams::getMarketplaces()[0];
$offerRequests = [];
foreach($skus as $sku) {
    $offerRequests[] = new ListingOffersRequest([
        'uri' => '/products/pricing/v0/listings/'.urlencode($sku).'/offers',
        'method' => new HttpMethod(HttpMethod::GET),
        'item_condition' => new ItemCondition(ItemCondition::_NEW),
        'marketplace_id' => $marketplaceId,
        'customer_type' => new CustomerType(CustomerType::CONSUMER)
    ]);
}
$batchRequest = new GetListingOffersBatchRequest(['requests' => $offerRequests]);
$response = $sdk->productPricing()->getListingOffersBatch($accessToken, Regions::EUROPE, $batchRequest);

foreach($response->getResponses() as $resp) {
    $body = $resp->getBody()->getPayload();

    $prices = [];
    $priceId = 1;
    $used = 0;
    $new = 0;
    foreach($body->getOffers() as $offer) {
        if($offer->getIsBuyBoxWinner()) {
            $prices[] = '<CompetitivePrice belongsToRequester="'.($offer->getMyOffer() ? 'true' : 'false').'"
                        condition="'.($offer->getSubCondition() == 'new' ? 'New' : 'Used').'"
                        subcondition="'.ucfirst($offer->getSubCondition()).'">
                    <CompetitivePriceId>'.$priceId++.'</CompetitivePriceId>
                    <Price>
                        <LandedPrice>
                            <CurrencyCode>'.($offer->getListingPrice()->getCurrencyCode()).'</CurrencyCode>
                            <Amount>'.($offer->getListingPrice()->getAmount()+$offer->getShipping()->getAmount()).'</Amount>
                        </LandedPrice>
                        <ListingPrice>
                            <CurrencyCode>'.$offer->getListingPrice()->getCurrencyCode().'</CurrencyCode>
                            <Amount>'.$offer->getListingPrice()->getAmount().'</Amount>
                        </ListingPrice>
                        <Shipping>
                            <CurrencyCode>'.$offer->getShipping()->getCurrencyCode().'</CurrencyCode>
                            <Amount>'.$offer->getShipping()->getAmount().'</Amount>
                        </Shipping>
                    </Price>
                </CompetitivePrice>'.PHP_EOL;
        }
        if($offer->getSubCondition() == 'new') {
            $new++;
        }else{
            $used++;
        }
    }

    $responses[] = '
            <GetCompetitivePricingForSKUResult SellerSKU="'.$resp->getRequest()->getSellerSKU().'" status="'
        .$resp->getStatus()->getStatusCode().'">

                <Product xmlns="http://mws.amazonservices.com/schema/Products/2011-10-01"
                    xmlns:ns2="http://mws.amazonservices.com/schema/Products/2011-10-01/default.xsd">
                    <Identifiers>
                        <MarketplaceASIN>
                            <MarketplaceId>'.$resp->getBody()->getPayload()->getIdentifier()->getMarketplaceId().'</MarketplaceId>
                            <ASIN>'.$resp->getBody()->getPayload()->getIdentifier()->getAsin().'</ASIN>
                        </MarketplaceASIN>
                        <SKUIdentifier>
                            <MarketplaceId>'.$resp->getBody()->getPayload()->getIdentifier()->getMarketplaceId().'</MarketplaceId>
                            <SellerId>'.$sellerId.'</SellerId>
                            <SellerSKU>'.$resp->getBody()->getPayload()->getIdentifier()->getSellerSku().'</SellerSKU>
                        </SKUIdentifier>
                    </Identifiers>
                    <CompetitivePricing>
                        <CompetitivePrices>
                            '.implode(PHP_EOL, $prices).'
                        </CompetitivePrices>
                        <NumberOfOfferListings>
                            <OfferListingCount condition="Any">'.($new+$used).'</OfferListingCount>
                            <OfferListingCount condition="Used">'.$used.'</OfferListingCount>
                            <OfferListingCount condition="New">'.$new.'</OfferListingCount>
                        </NumberOfOfferListings>'.PHP_EOL.
                    /*
                     <TradeInValue>
                        <CurrencyCode>USD</CurrencyCode>
                        <Amount>17.05</Amount>
                    </TradeInValue>
                     */
                    '</CompetitivePricing>'.PHP_EOL.
                /*
                 <SalesRankings>
                    <SalesRank>
                        <ProductCategoryId>book_display_on_website</ProductCategoryId>
                        <Rank>900</Rank>
                    </SalesRank>
                    <SalesRank>
                        <ProductCategoryId>271578011</ProductCategoryId>
                        <Rank>1</Rank>
                    </SalesRank>
                    <SalesRank>
                        <ProductCategoryId>355562011</ProductCategoryId>
                        <Rank>8</Rank>
                    </SalesRank>
                    <SalesRank>
                        <ProductCategoryId>173516</ProductCategoryId>
                        <Rank>25</Rank>
                    </SalesRank>
                </SalesRankings>
                 */
                '</Product>
            </GetCompetitivePricingForSKUResult>';
}


echo '<?xml version="1.0"?>
<GetCompetitivePricingForSKUResponse
    xmlns="http://mws.amazonservices.com/schema/Products/2011-10-01">
    '.implode(PHP_EOL, $responses).'
    <ResponseMetadata>
        <RequestId>'.uniqid().'</RequestId>
    </ResponseMetadata>
</GetCompetitivePricingForSKUResponse>'.PHP_EOL;
