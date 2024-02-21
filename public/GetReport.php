<?php

use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\OrderAdapter;
use AmazonMWSSPWrapper\AmazonSP\RequestParams;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';
$logger = new DebugLogger();
$sdk = SdkConfig::getSdk();
$accessToken = SdkConfig::getAccessToken();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($queryString, $queryParams);

$report = $sdk->reports()->getReport($accessToken, Regions::EUROPE, $queryParams["ReportId"]);
if($report->getProcessingStatus() == 'DONE') {
    $documentId = $report->getReportDocumentId();
    $document = $sdk->reports()->getReportDocument($accessToken, Regions::EUROPE, $documentId);
    $url = $document->getUrl();
    $documentContent = file_get_contents($url);
    $decodedContent = maybeDecompress($documentContent);
    echo $decodedContent;
}


function maybeDecompress($content) {
    // Check for gzip signature
    if (substr($content, 0, 2) === "\x1F\x8B") {
        return gzdecode($content);
    }
    return $content;
}
