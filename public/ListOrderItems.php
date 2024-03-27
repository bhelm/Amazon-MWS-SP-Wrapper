<?php

use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';
$sdk = SdkConfig::getSdk();
$logger = new DebugLogger();
$accessToken = SdkConfig::getAccessToken();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($queryString, $queryParams);

$amazonOrderId = $queryParams["AmazonOrderId"];
$items = $sdk->orders()->getOrderItems($accessToken, Regions::EUROPE, $amazonOrderId);
$xml = new SimpleXMLElement('<ListOrderItemsResponse xmlns="https://mws.amazonservices.com/Orders/2013-09-01"></ListOrderItemsResponse>');
$xml->addChild('ListOrderItemsResult');
$xml->ListOrderItemsResult->addChild('AmazonOrderId', $amazonOrderId);
$orderItems = $xml->ListOrderItemsResult->addChild('OrderItems');

foreach($items->getPayload()->getOrderItems() as $item) {
$orderItem = $orderItems->addChild('OrderItem');
$orderItem->addChild('ASIN', $item->getAsin());
$orderItem->addChild('SellerSKU', $item->getSellerSKU());
$orderItem->addChild('OrderItemId', $item->getOrderItemId());
$orderItem->addChild('Title', $item->getTitle());
$orderItem->addChild('QuantityOrdered', $item->getQuantityOrdered());
$orderItem->addChild('QuantityShipped', $item->getQuantityShipped());

$productInfo = $orderItem->addChild('ProductInfo');
$productInfo->addChild('NumberOfItems', $item->getProductInfo()->getNumberOfItems());
if($item->getItemPrice()) {
    $itemPrice = $orderItem->addChild('ItemPrice');

    $itemPrice->addChild('CurrencyCode', $item->getItemPrice()->getCurrencyCode());
    $itemPrice->addChild('Amount', $item->getItemPrice()->getAmount());
}

$itemTax = $orderItem->addChild('ItemTax');
$itemTax->addChild('CurrencyCode', $item->getItemTax()->getCurrencyCode());
$itemTax->addChild('Amount', $item->getItemTax()->getAmount());

$promotionDiscount = $orderItem->addChild('PromotionDiscount');
$promotionDiscount->addChild('CurrencyCode', $item->getPromotionDiscount()->getCurrencyCode());
$promotionDiscount->addChild('Amount', $item->getPromotionDiscount()->getAmount());

$promotionDiscountTax = $orderItem->addChild('PromotionDiscountTax');
$promotionDiscountTax->addChild('CurrencyCode', $item->getPromotionDiscountTax()->getCurrencyCode());
$promotionDiscountTax->addChild('Amount', $item->getPromotionDiscountTax()->getAmount());

$orderItem->addChild('IsGift', $item->getIsGift() ? 'true' : 'false');
$orderItem->addChild('ConditionId', $item->getConditionId());
$orderItem->addChild('ConditionSubtypeId', $item->getConditionSubtypeId());
$orderItem->addChild('IsTransparency', $item->getIsTransparency() ? 'true' : 'false');

}
$responseMetadata = $xml->addChild('ResponseMetadata');
$responseMetadata->addChild('RequestId', '88faca76-b600-46d2-b53c-0c8c4533e43a'); // Placeholder

$outputXML = $xml->asXML();
//$outputXML = iconv('UTF-8', 'ISO-8859-1', $outputXML);
echo $outputXML;
