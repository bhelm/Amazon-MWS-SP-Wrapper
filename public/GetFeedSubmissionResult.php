<?php

use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';
$logger = new DebugLogger();
$sdk = SdkConfig::getSdk();
$accessToken = SdkConfig::getAccessToken();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($queryString, $queryParams);

$feedSubmissionId = $queryParams['FeedSubmissionId'];
$feed = $sdk->feeds()->getFeed($accessToken, Regions::EUROPE, $feedSubmissionId);
if($feed->getProcessingStatus() == 'DONE') {
    $documentId = $feed->getResultFeedDocumentId();
    $document = $sdk->feeds()->getFeedDocument($accessToken, Regions::EUROPE, $documentId);
    $content = file_get_contents($document->getUrl());
    //$content = mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
    echo $content;
}
