<?php

namespace AmazonMWSSPWrapper\AmazonSP;

class ReportAdapter
{
    private static array $reportMap = [
        'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE' => 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL',
        'GET_MERCHANT_LISTINGS_DEFECT_DATA' => 'GET_MERCHANTS_LISTINGS_FYP_REPORT',
        'LISTINGS_DEFECT_REPORT_XL' => 'GET_MERCHANTS_LISTINGS_FYP_REPORT',
        'GET_FLAT_FILE_ACTIONABLE_ORDER_DATA' => 'GET_FLAT_FILE_ACTIONABLE_ORDER_DATA_SHIPPING',
        'GET_ORDERS_DATA' => 'GET_ORDER_REPORT_DATA_SHIPPING',
        'GET_FLAT_FILE_ORDERS_DATA' => 'GET_FLAT_FILE_ORDER_REPORT_DATA_SHIPPING',

    ];

    public static function mwsToSp(?string $reportType) {
        if($reportType == null) {
            return null;
        }
        $reportType = trim($reportType, '_');

        // Check if the reportType exists in the reportMap, if so, return the mapped value
        if (isset(self::$reportMap[$reportType])) {
            return self::$reportMap[$reportType];
        }

        // If not found in the map, return the original reportType
        return $reportType;
    }

    public static function spToMws(?string $reportType) {
        if($reportType == null) {
            return null;
        }
        // Search for the reportType in the reportMap values and return the corresponding key
        $mwsReportType = array_search($reportType, self::$reportMap);

        // If found, wrap it with underscores and return, otherwise wrap the original reportType with underscores
        return $mwsReportType ? "_{$mwsReportType}_" : "_{$reportType}_";
    }
}
