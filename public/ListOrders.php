<?php

use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\OrderAdapter;
use AmazonMWSSPWrapper\AmazonSP\RequestParams;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';
$logger = new DebugLogger();
if(!isset($mode)) {
    $mode = "ListOrders";
}
$sdk = SdkConfig::getSdk();
$accessToken = SdkConfig::getAccessToken();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($queryString, $queryParams);

$marketplaceIds = RequestParams::extractMultipleValues('MarketplaceId_Id', $queryParams);
if(empty($marketplaceIds)) {
    $marketplaceIds = [Marketplace::DE()->id()];
}
$orderStatuses = RequestParams::extractMultipleValues('OrderStatus_Status', $queryParams);
$fulfillmentChannels = RequestParams::extractMultipleValues('FulfillmentChannel_Channel', $queryParams);
$amazonOrderIds = RequestParams::extractMultipleValues('AmazonOrderId_Id', $queryParams);
$ordersResult = $sdk->orders()->getOrders(
    $accessToken,
    Regions::EUROPE,
    $marketplaceIds,
    RequestParams::formatDateTime($queryParams["CreatedAfter"] ?? null),
    RequestParams::formatDateTime($queryParams["CreatedBefore"] ?? null),
    RequestParams::formatDateTime($queryParams["LastUpdatedAfter"] ?? null),
    RequestParams::formatDateTime($queryParams["LastUpdatedBefore"] ?? null),
     empty($orderStatuses) ? null : $orderStatuses,
    empty($fulfillmentChannels) ? null : $fulfillmentChannels,
    isset($queryParams["PaymentMethod"]) ? [$queryParams["PaymentMethod"]] : null, // Same assumption as above.
    $queryParams["BuyerEmail"] ?? null,
    $queryParams["SellerOrderId"] ?? null,
    isset($queryParams["MaxResultsPerPage"]) ? (int) $queryParams["MaxResultsPerPage"] : null,
    isset($queryParams["EasyShipShipmentStatus"]) ? [$queryParams["EasyShipShipmentStatus"]] : null, // Same assumption as above.
    null, // electronic_invoice_statuses
    $queryParams["NextToken"] ?? null, // next_token
     empty($amazonOrderIds) ? null : $amazonOrderIds, // amazon_order_ids
    null, // actual_fulfillment_supply_source_id
    null, // is_ispu
    null, // store_chain_store_id
    null, // item_approval_types
    null  // item_approval_status
);

$xml = new SimpleXMLElement('<'.$mode.'Response xmlns="https://mws.amazonservices.com/Orders/2013-09-01"></'.$mode.'Response>');
$getOrderResult = $xml->addChild($mode.'Result');
$orders = $getOrderResult->addChild('Orders');

foreach($ordersResult->getPayload()->getOrders() as $order) {
    OrderAdapter::appendOrderXml($orders, $order);
}

$responseMetadata = $xml->addChild('ResponseMetadata');
$responseMetadata->addChild('RequestId', '88faca76-b600-46d2-b53c-0c8c4533e43a');  // Adjust the value if necessary
if($ordersResult->getPayload()->getNextToken() != null) {
    $getOrderResult->addChild('NextToken', $ordersResult->getPayload()->getNextToken());
}


$outputXML = $xml->asXML();
//$outputXML = iconv('U1TF-8', 'ISO-8859-1', $outputXML);
echo $outputXML.PHP_EOL;
