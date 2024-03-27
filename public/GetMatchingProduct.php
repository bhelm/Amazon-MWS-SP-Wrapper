<?php

use AmazonMWSSPWrapper\AmazonSP\CatalogItem;
use AmazonMWSSPWrapper\AmazonSP\RequestParams;
use AmazonPHP\SellingPartner\Model\CatalogItem\Dimensions;
use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';

$sdk = SdkConfig::getSdk();
$logger = new DebugLogger();
$accessToken = SdkConfig::getAccessToken();
if(!isset($mode)) {
    $mode = "";
}

$marketplaceIds = RequestParams::getMarketplaces();
$asins = RequestParams::extractMultipleValues('ASINList_ASIN');

$responses = [];

foreach($asins as $asin) {
        $catalogInfo = $sdk->catalogItem()->getCatalogItem($accessToken,
            Regions::EUROPE,
            $asin,
            $marketplaceIds,
            [ 'salesRanks', 'productTypes', 'attributes', 'identifiers', 'summaries', 'dimensions']
        //[ 'relationships',  'images', ]
        );

        $responses[] = '<GetMatchingProductResult ASIN="'.$catalogInfo->getAsin().'" status="Success">
        '.CatalogItem::renderItem($catalogInfo).'
    </GetMatchingProductResult>';
}

echo '<?xml version="1.0"?>
<GetMatchingProductResponse
    xmlns="http://mws.amazonservices.com/schema/Products/2011-10-01">
    '.implode(PHP_EOL, $responses).'
    <ResponseMetadata>
        <RequestId>'.uniqid().'</RequestId>
    </ResponseMetadata>
</GetMatchingProductResponse>'.PHP_EOL;

