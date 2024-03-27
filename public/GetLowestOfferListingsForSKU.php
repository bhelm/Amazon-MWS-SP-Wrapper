<?php

use AmazonMWSSPWrapper\AmazonSP\RequestParams;
use AmazonPHP\SellingPartner\Model\ProductPricing\CustomerType;
use AmazonPHP\SellingPartner\Model\ProductPricing\GetListingOffersBatchRequest;
use AmazonPHP\SellingPartner\Model\ProductPricing\HttpMethod;
use AmazonPHP\SellingPartner\Model\ProductPricing\ItemCondition;
use AmazonPHP\SellingPartner\Model\ProductPricing\ListingOffersRequest;
use AmazonPHP\SellingPartner\Model\ProductPricing\Product;
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

        $listings = [];

        foreach($body->getOffers() as $offer) {
            $listings[] = '<LowestOfferListing>
                    <Qualifiers>
                        <ItemCondition>'.$offer->getSubCondition().'</ItemCondition>
                        <ItemSubcondition>'.$offer->getSubCondition().'</ItemSubcondition>
                        <FulfillmentChannel>'.($offer->getIsFulfilledByAmazon() ? 'Amazon' : 'Merchant').'</FulfillmentChannel>
                        <ShipsDomestically>'.(($offer->getShipsFrom() && $offer->getShipsFrom()->getCountry() ?? 'DE') == 'DE' ? 'True' : 'False').'</ShipsDomestically>
                        <ShippingTime>
                            <Max>'.mapShippingTime((int)round(($offer->getShippingTime()->getMaximumHours() ?? 0) / 24, 0)).'</Max>
                        </ShippingTime>
                        <SellerPositiveFeedbackRating>'
                        .mapRating($offer->getSellerFeedbackRating()->getSellerPositiveFeedbackRating())
                      .'</SellerPositiveFeedbackRating>
                    </Qualifiers>
                    <SellerFeedbackCount>'.$offer->getSellerFeedbackRating()->getFeedbackCount().'</SellerFeedbackCount>
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
                </LowestOfferListing>'.PHP_EOL;
        }


        $responses[] = '
            <GetLowestOfferListingsForSKUResult SellerSKU="'.$resp->getRequest()->getSellerSKU().'" status="'
            .$resp->getStatus()->getStatusCode().'">
                <AllOfferListingsConsidered>false</AllOfferListingsConsidered>
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
                    <LowestOfferListings>
                        '.implode("", $listings).'
                    </LowestOfferListings>
                </Product>
            </GetLowestOfferListingsForSKUResult>';
        }


echo '<?xml version="1.0"?>
<GetLowestOfferListingsForSKUResponse
    xmlns="http://mws.amazonservices.com/schema/Products/2011-10-01">
    '.implode($responses).'
    <ResponseMetadata>
        <RequestId>'.uniqid().'</RequestId>
    </ResponseMetadata>
</GetLowestOfferListingsForSKUResponse>'.PHP_EOL;


function mapRating($value) {
    $ranges = [
        'Just Launched' => [0, 0],
        'Less than 70%' => [1, 69],
        '70-79%' => [70, 79],
        '80-89%' => [80, 89],
        '90-94%' => [90, 94],
        '95-97%' => [95, 97],
        '98-100%' => [98, 100],
    ];

    $closestRange = '';
    $closestDiff = PHP_INT_MAX;

    foreach ($ranges as $rangeKey => $range) {
        if ($value >= $range[0] && $value <= $range[1]) {
            // If the value is exactly within a range, return it immediately
            return $rangeKey;
        } else {
            // Check if this range is closer to the value than what we have found before
            $diff = min(abs($value - $range[0]), abs($value - $range[1]));
            if ($diff < $closestDiff) {
                $closestDiff = $diff;
                $closestRange = $rangeKey;
            }
        }
    }

    return $closestRange;
}

function mapShippingTime($value) {
    $ranges = [
        '0-2 days' => [0, 2],
        '3-7 days' => [3, 7],
        '8-13 days' => [8, 13],
        '14 or more days' => [14, PHP_INT_MAX], // Assuming no upper limit for "14 or more days"
    ];

    foreach ($ranges as $rangeKey => $range) {
        if ($value >= $range[0] && $value <= $range[1]) {
            // If the value is exactly within a range, return it immediately
            return $rangeKey;
        }
    }

    // In case the value doesn't fit any range, though it should always fit the "14 or more days" range
    return 'Value does not fit any range';
}