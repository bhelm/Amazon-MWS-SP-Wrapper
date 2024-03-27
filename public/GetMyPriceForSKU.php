<?php

use AmazonMWSSPWrapper\AmazonSP\RequestParams;
use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';

$sdk = SdkConfig::getSdk();
$logger = new DebugLogger();
$accessToken = SdkConfig::getAccessToken();

$skus = RequestParams::extractMultipleValues('SellerSKUList_SellerSKU');
$marketplaceId = RequestParams::getMarketplaces()[0];
$itemCondition = RequestParams::getParams()['ItemCondition'] ?? 'New';

$pricing = $sdk->productPricing()->getPricing($accessToken,
    Regions::EUROPE,
    $marketplaceId,
    'Sku',
    [],
    $skus,
    $itemCondition);

$responses = [];

foreach($pricing->getPayload() as $priceInfo) {

    $product = $priceInfo->getProduct();
    $offerResults=[];
    $offers = $product->getOffers();
    if($offers) {
        foreach($offers as $offer) {
            $offerResults[] = '<Offer>
                    <BuyingPrice>
                        <LandedPrice>
                            <CurrencyCode>'.$offer->getBuyingPrice()->getLandedPrice()->getCurrencyCode().'</CurrencyCode>
                            <Amount>'.$offer->getBuyingPrice()->getLandedPrice()->getAmount().'</Amount>
                        </LandedPrice>
                        <ListingPrice>
                            <CurrencyCode>'.$offer->getBuyingPrice()->getListingPrice()->getCurrencyCode().'</CurrencyCode>
                            <Amount>'.$offer->getBuyingPrice()->getListingPrice()->getAmount().'</Amount>
                        </ListingPrice>
                        <Shipping>
                            <CurrencyCode>'.$offer->getBuyingPrice()->getShipping()->getCurrencyCode().'</CurrencyCode>
                            <Amount>'.$offer->getBuyingPrice()->getShipping()->getAmount().'</Amount>
                        </Shipping>
                    </BuyingPrice>
                    <RegularPrice>
                        <CurrencyCode>'.$offer->getRegularPrice()->getCurrencyCode().'</CurrencyCode>
                        <Amount>'.$offer->getRegularPrice()->getAmount().'</Amount>
                    </RegularPrice>
                    <FulfillmentChannel>'.$offer->getFulfillmentChannel().'</FulfillmentChannel>
                    <ItemCondition>'.$offer->getItemCondition().'</ItemCondition>
                    <ItemSubCondition>'.$offer->getItemSubCondition().'</ItemSubCondition>
                    <SellerId>'.$product->getIdentifiers()->getSkuIdentifier()->getSellerId().'</SellerId>
                    <SellerSKU>'.$offer->getSellerSku().'</SellerSKU>
                </Offer>';
        }
    }

    $responses[] = '<GetMyPriceForSKUResult SellerSKU="'.$priceInfo->getSellerSku().'" status="'
    .$priceInfo->getStatus().'">
        <Product xmlns="http://mws.amazonservices.com/schema/Products/2011-10-01"
            xmlns:ns2="http://mws.amazonservices.com/schema/Products/2011-10-01/default.xsd">
            <Identifiers>
                <MarketplaceASIN>
                    <MarketplaceId>'.$product->getIdentifiers()->getMarketplaceAsin()->getMarketplaceId().'</MarketplaceId>
                    <ASIN>'.$product->getIdentifiers()->getMarketplaceAsin()->getAsin().'</ASIN>
                </MarketplaceASIN>
                <SKUIdentifier>
                    <MarketplaceId>'.$product->getIdentifiers()->getSkuIdentifier()->getMarketplaceId().'</MarketplaceId>
                    <SellerId>'.$product->getIdentifiers()->getSkuIdentifier()->getSellerId().'</SellerId>
                    <SellerSKU>'.$product->getIdentifiers()->getSkuIdentifier()->getSellerSku().'</SellerSKU>
                </SKUIdentifier>
            </Identifiers>
            <Offers>
                '.implode(PHP_EOL, $offerResults).'
            </Offers>
        </Product>
    </GetMyPriceForSKUResult>';
}


echo '<?xml version="1.0"?>
<GetMyPriceForSKUResponse
    xmlns="http://mws.amazonservices.com/schema/Products/2011-10-01">
    '.implode(PHP_EOL, $responses).'
    <ResponseMetadata>
        <RequestId>'.uniqid().'</RequestId>
    </ResponseMetadata>
</GetMyPriceForSKUResponse>'.PHP_EOL;
