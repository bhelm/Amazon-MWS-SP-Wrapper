<?php

use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\OrderAdapter;
use AmazonMWSSPWrapper\AmazonSP\ReportAdapter;
use AmazonMWSSPWrapper\AmazonSP\RequestParams;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';
$logger = new DebugLogger();
$sdk = SdkConfig::getSdk();
$accessToken = SdkConfig::getAccessToken();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($queryString, $queryParams);

$marketplaceIds = RequestParams::getMarketplaces();

$reportIds = RequestParams::extractMultipleValues('ReportRequestIdList_Id');
$reportTypeList = RequestParams::extractMultipleValues('ReportTypeList_Type') ?? [];
$reportTypeListConverted = [];
foreach($reportTypeList as $reportType) {
    $reportTypeListConverted[] = trim($reportType, '_');
}
unset($reportTypeList);
$queryParams = RequestParams::getParams();

$reports = [];

if(!empty($reportIds)) {
    foreach($reportIds as $reportId) {
        try {
            $reports[] = $sdk->reports()->getReport($accessToken, Regions::EUROPE, $reportId);
        }catch (Exception $e) {
            // well
        }
    }
}else{
    $reportsRequest = $sdk->reports()->getReports($accessToken,
        Regions::EUROPE,
        $reportTypeListConverted,
        null,
        $marketplaceIds,
        100,
        RequestParams::formatDateTime($queryParams["AvailableFromDate"], true),
        RequestParams::formatDateTime($queryParams["AvailableToDate"], true),
        null
    );
    $reports = $reportsRequest->getReports();

}

// Create a new SimpleXMLElement
$xml = new SimpleXMLElement('<?xml version="1.0"?><GetReportListResponse xmlns="http://mws.amazonservices.com/doc/2009-01-01/"></GetReportListResponse>');
$result = $xml->addChild('GetReportListResult');

foreach($reports as $report) {

    $reportInfo = $result->addChild('ReportInfo');
    $reportInfo->addChild('ReportId', $report->getReportId());

    $reportType = ReportAdapter::spToMws($report->getReportType());

    $reportInfo->addChild('ReportType', $reportType);
    $reportInfo->addChild('ReportRequestId', $report->getReportId());
    $reportInfo->addChild('AvailableDate', $report->getCreatedTime()->format('Y-m-d\TH:i:s\Z'));
    $reportInfo->addChild('Acknowledged', 'false');
};

$metadata = $xml->addChild('ResponseMetadata');
$metadata->addChild('RequestId', uniqid());

// Display the XML
header('Content-Type: application/xml');
echo $xml->asXML();
