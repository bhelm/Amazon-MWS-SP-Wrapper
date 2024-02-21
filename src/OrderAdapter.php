<?php

namespace AmazonMWSSPWrapper\AmazonSP;

use AmazonPHP\SellingPartner\Regions;
use Exception;
use SimpleXMLElement;

class OrderAdapter
{
    public static function appendOrderXml(SimpleXMLElement $parent, object $order) {
        $buyerInfo = null;
        if($order->getOrderStatus() != "Pending") {
            try{
                $buyerInfo = SdkConfig::getSdk()->orders()->getOrderBuyerInfo(SdkConfig::getAccessToken(), Regions::EUROPE,
                    $order->getAmazonOrderId());
            }catch (Exception $e) {
                // maybe its not available
            }
        }
        $orderNode = $parent->addChild('Order');

        $orderNode->addChild('AmazonOrderId', $order->getAmazonOrderId());
        $orderNode->addChild('PurchaseDate', $order->getPurchaseDate());
        $orderNode->addChild('LastUpdateDate', $order->getLastUpdateDate());
        $orderNode->addChild('OrderStatus', $order->getOrderStatus());
        if($buyerInfo) {
            if ($buyerInfo->getPayload()->getBuyerEmail()) {
                $orderNode->addChild('BuyerEmail', $buyerInfo->getPayload()->getBuyerEmail());
            }
            if ($buyerInfo->getPayload()->getBuyerName()) {
                $orderNode->addChild('BuyerName', $buyerInfo->getPayload()->getBuyerName());
            }
        }

        $orderNode->addChild('FulfillmentChannel', $order->getFulfillmentChannel());
        $orderNode->addChild('NumberOfItemsShipped', $order->getNumberOfItemsShipped());
        $orderNode->addChild('NumberOfItemsUnshipped', $order->getNumberOfItemsUnshipped());
        $orderNode->addChild('PaymentMethod', $order->getPaymentMethod());

        $paymentMethodDetails = $orderNode->addChild('PaymentMethodDetails');
        foreach ($order->getPaymentMethodDetails() as $detail) {
            $paymentMethodDetails->addChild('PaymentMethodDetail', $detail);
        }

        $orderNode->addChild('MarketplaceId', $order->getMarketplaceId());
        $orderNode->addChild('ShipmentServiceLevelCategory', $order->getShipmentServiceLevelCategory());
        $orderNode->addChild('OrderType', $order->getOrderType());
        $orderNode->addChild('EarliestShipDate', $order->getEarliestShipDate());
        $orderNode->addChild('LatestShipDate', $order->getLatestShipDate());
        $orderNode->addChild('IsBusinessOrder', $order->getIsBusinessOrder() ? 'true' : 'false');
        $orderNode->addChild('IsPrime', $order->getIsPrime() ? 'true' : 'false');
        $orderNode->addChild('IsPremiumOrder', $order->getIsPremiumOrder() ? 'true' : 'false');
        $orderNode->addChild('IsGlobalExpressEnabled', $order->getIsGlobalExpressEnabled() ? 'true' : 'false');
        $orderNode->addChild('IsAccessPointOrder', $order->getIsAccessPointOrder() ? 'true' : 'false');
    }
}
