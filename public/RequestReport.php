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

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($queryString, $queryParams);

$reportType = ReportAdapter::mwsToSp($queryParams['ReportType'] ?? null);

$startDate = RequestParams::formatDateTime($queryParams["StartDate"]);

$reportSpec = new CreateReportSpecification([
    'report_type' => $reportType,
    'data_start_time' => RequestParams::formatDateTime($queryParams["StartDate"], true),
    'data_end_time' => RequestParams::formatDateTime($queryParams["EndDate"], true),
    'marketplace_ids' => RequestParams::getMarketplaces(),
    'report_options' => RequestParams::extractMultipleValues('ReportOptions', $queryParams)
]);
$resp = $sdk->reports()->createReport($accessToken, Regions::EUROPE, $reportSpec );


// Create the root element
$xml = new SimpleXMLElement('<?xml version="1.0"?><RequestReportResponse></RequestReportResponse>');
$xml->addAttribute('xmlns', 'http://mws.amazonaws.com/doc/2009-01-01/');

// Add child elements
$requestReportResult = $xml->addChild('RequestReportResult');
$reportRequestInfo = $requestReportResult->addChild('ReportRequestInfo');
$reportRequestInfo->addChild('ReportRequestId', $resp->getReportId());
$reportRequestInfo->addChild('ReportType', $queryParams['ReportType']);
if(isset($queryParams["StartDate"])) {
    $reportRequestInfo->addChild('StartDate', $queryParams["StartDate"]);
}
if(isset($queryParams["EndDate"])) {
    $reportRequestInfo->addChild('EndDate', $queryParams["EndDate"]);
}
$reportRequestInfo->addChild('Scheduled', 'false');
$reportRequestInfo->addChild('SubmittedDate', (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'));
$reportRequestInfo->addChild('ReportProcessingStatus', '_SUBMITTED_');

$responseMetadata = $xml->addChild('ResponseMetadata');
$responseMetadata->addChild('RequestId', uniqid());

// Display the XML
header('Content-Type: text/xml');
echo $xml->asXML();
