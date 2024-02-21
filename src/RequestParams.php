<?php

namespace AmazonMWSSPWrapper\AmazonSP;

use AmazonPHP\SellingPartner\Marketplace;

class RequestParams
{
    private static $queryParams = [];

    public static function getParams() {
        if(!empty(self::$queryParams)) {
            return self::$queryParams;
        }
        $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        parse_str($queryString, self::$queryParams);
        return self::$queryParams;
    }
    public static function formatDateTime($dateString, $asDateTime=false) {
        if (empty($dateString)) {
            return null;
        }
        $result = $dateString;

        // Check if the string has the format of "Txx:xx:xx.xxxZ"
        if (preg_match('/T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $dateString)) {
            $result = substr($dateString, 0, -5).'Z';
        }

        if($result && $asDateTime) {
            return new \DateTime($result);
        }

        return $dateString;
    }

    // A function to extract multiple values from the query parameters
    public static function extractMultipleValues($paramBaseName) {
        $queryParams = self::getParams();
        $values = [];
        $index = 1;
        while (isset($queryParams["{$paramBaseName}_{$index}"])) {
            $values[] = $queryParams["{$paramBaseName}_{$index}"];
            $index++;
        }
        if (empty($values)) {
            return null;
        }
        return $values;
    }

    public static function getMarketplaces() {
        $queryParams = self::getParams();

        RequestParams::extractMultipleValues('MarketplaceId_Id');
        if(empty($marketplaceIds) && isset($queryParams['Marketplace'])) {
            $marketplaceIds = [$queryParams['Marketplace']];
        }

        if(empty($marketplaceIds)) {
            $marketplaceIds = [Marketplace::DE()->id()];
        }
        return $marketplaceIds;
    }
}
