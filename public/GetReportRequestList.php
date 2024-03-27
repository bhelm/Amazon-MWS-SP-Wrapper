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
$sdk = SdkConfig::getSdk();
$logger = new DebugLogger();
$accessToken = SdkConfig::getAccessToken();

$marketplaceIds = RequestParams::getMarketplaces();

$reportIds = RequestParams::extractMultipleValues('ReportRequestIdList_Id');
$reportTypeList = RequestParams::extractMultipleValues('ReportTypeList_Type') ?? [];
$reportTypeListConverted = [];
foreach($reportTypeList as $reportType) {
    $reportTypeListConverted[] = trim($reportType, '_');
}
unset($reportTypeList);
$queryParams = RequestParams::getParams();

$reportProcessingStatusList = RequestParams::extractMultipleValues('ReportProcessingStatusList') ?? [];
$reportProcessingStatusListConverted = [];
foreach($reportProcessingStatusList as $reportProcessingStatus) {
    $reportProcessingStatusListConverted[] = trim($reportProcessingStatus, '_');
}
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
        $reportProcessingStatusListConverted,
        $marketplaceIds,
        $queryParams["MaxCount"] ?? 100,
        RequestParams::formatDateTime($queryParams["RequestedFromDate"], true),
        RequestParams::formatDateTime($queryParams["RequestedToDate"], true),
        null
    );
    $reports = $reportsRequest->getReports();

}

// Create a new SimpleXMLElement
$xml = new SimpleXMLElement('<?xml version="1.0"?><GetReportRequestListResponse xmlns="http://mws.amazonservices.com/doc/2009-01-01/"></GetReportRequestListResponse>');
$result = $xml->addChild('GetReportRequestListResult');

foreach($reports as $report) {
    $reportInfo = $result->addChild('ReportRequestInfo');
    $reportType = ReportAdapter::spToMws($report->getReportType());
    $reportInfo->addChild('ReportType', $reportType);
    $reportInfo->addChild('ReportRequestId', $report->getReportId());
    $reportInfo->addChild('StartedProcessingDate', $report->getProcessingStartTime()->format('Y-m-d\TH:i:s\Z'));
    $reportInfo->addChild('StartDate', $report->getDataStartTime()->format('Y-m-d\TH:i:s\Z'));
    $reportInfo->addChild('EndDate', $report->getDataEndTime()->format('Y-m-d\TH:i:s\Z'));
    $reportInfo->addChild('Scheduled', 'false');
    $reportInfo->addChild('ReportProcessingStatus', '_'.$report->getProcessingStatus().'_');
    $reportInfo->addChild('GeneratedReportId', $report->getReportId());
    $reportInfo->addChild('CompletedDate', $report->getProcessingEndTime()->format('Y-m-d\TH:i:s\Z'));
    $reportInfo->addChild('SubmittedDate', $report->getCreatedTime()->format('Y-m-d\TH:i:s\Z'));
};

$metadata = $xml->addChild('ResponseMetadata');
$metadata->addChild('RequestId', uniqid()); // Hardcoded from your example

// Display the XML
header('Content-Type: application/xml');
echo $xml->asXML();
