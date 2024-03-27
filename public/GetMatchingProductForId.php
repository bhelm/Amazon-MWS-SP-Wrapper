<?php

use AmazonMWSSPWrapper\AmazonSP\CatalogItem;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\RequestParams;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;
use AmazonPHP\SellingPartner\Regions;

require __DIR__ . '/../vendor/autoload.php';

$sdk = SdkConfig::getSdk();
$logger = new DebugLogger();
$accessToken = SdkConfig::getAccessToken();

$ids = RequestParams::extractMultipleValues('IdList_Id');
$marketplaceIds = RequestParams::getMarketplaces();
$idType = RequestParams::getParams()['IdType'];

$responses = [];

$catalogInfo = $sdk->catalogItem()->searchCatalogItems($accessToken,
        Regions::EUROPE,
        $marketplaceIds,
        $ids,
        $idType,
        ['salesRanks', 'productTypes', 'attributes', 'identifiers', 'summaries', 'dimensions']
    //[ 'relationships',  'images', ]
    );

foreach($catalogInfo->getItems() as $item) {
    //TODO: figure out how to map that correctly (search all identifiers?)
    // <GetMatchingProductForIdResult Id="705110110990" IdType="EAN" status="Success">
    // meanwhile we return ean and hope the application does not rely on the original search identifier
    $responses[] = '<GetMatchingProductForIdResult ASIN="'.$item->getAsin().'" status="Success">
        '.CatalogItem::renderItem($item).'
    </GetMatchingProductForIdResult>';
}

echo '<?xml version="1.0"?>
<GetMatchingProductForIDResponse
    xmlns="http://mws.amazonservices.com/schema/Products/2011-10-01">
    '.implode(PHP_EOL, $responses).'
    <ResponseMetadata>
        <RequestId>'.uniqid().'</RequestId>
    </ResponseMetadata>
</GetMatchingProductForIDResponse>'.PHP_EOL;