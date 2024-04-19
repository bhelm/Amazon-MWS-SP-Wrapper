<?php

use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Model\Feeds\CreateFeedDocumentSpecification;
use AmazonPHP\SellingPartner\Model\Feeds\CreateFeedSpecification;
use AmazonPHP\SellingPartner\Regions;
use AmazonMWSSPWrapper\AmazonSP\DebugLogger;
use AmazonMWSSPWrapper\AmazonSP\SdkConfig;

require __DIR__ . '/../vendor/autoload.php';
$sdk = SdkConfig::getSdk();
$logger = new DebugLogger();

// Parse URL and extract FeedType
$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($queryString, $queryParams);

$accessToken = SdkConfig::getAccessToken();

$postData = file_get_contents('php://input');

$feedType = $queryParams['FeedType'] ?? null;
$feedType = trim($feedType, '_');
$contentType = (str_contains($feedType, 'FLAT')) ? "text/csv; charset=ISO-8859-1" : "text/xml; charset=ISO-8859-1";
try {
    $createFeedDocumentSpec = new CreateFeedDocumentSpecification([
        'content_type' => $contentType
    ]);

    $feedDocument = $sdk->feeds()->createFeedDocument($accessToken, Regions::EUROPE, $createFeedDocumentSpec);
} catch (Exception $e) {
    exit('Failed to create feed document: ' . $e->getMessage());
}

$url = $feedDocument->getUrl();
// Upload the feed content to the obtained URL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: '.$contentType));
curl_setopt($ch, CURLOPT_UPLOAD, 1);
curl_setopt($ch, CURLOPT_PUT, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$fileHandle = fopen('data://text/plain,' . $postData, 'r');
if (!$fileHandle) {
    exit('Failed to open data for upload');
}

curl_setopt($ch, CURLOPT_INFILE, $fileHandle);
curl_setopt($ch, CURLOPT_INFILESIZE, strlen($postData));
$response = curl_exec($ch);

$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if(curl_errno($ch)) {
    fclose($fileHandle);
    curl_close($ch);
    exit('cURL error during feed upload: ' . curl_error($ch));
}

curl_close($ch);
fclose($fileHandle);

if($httpStatusCode != 200) { // Assuming 200 is the expected status code
    exit("Failed to upload feed content to ".$url.". HTTP Status Code: {$httpStatusCode}, Response Body: {$response}");
}

try {
    $createFeedSpec = new CreateFeedSpecification([
            'feed_type' => $feedType,
            'marketplace_ids' => [Marketplace::DE()->id()],
            'input_feed_document_id' => $feedDocument->getFeedDocumentId()
        ]
    );

    $feedResponse = $sdk->feeds()->createFeed($accessToken, Regions::EUROPE, $createFeedSpec);
    $content = '<?xml version="1.0"?>
<SubmitFeedResponse xmlns="http://mws.amazonaws.com/doc/2009-01-01/">
<SubmitFeedResult>
    <FeedSubmissionInfo>
        <FeedSubmissionId>'.$feedResponse->getFeedId().'</FeedSubmissionId>
        <FeedType>_'.$feedType.'_</FeedType>
        <SubmittedDate>2023-10-12T15:05:44+00:00</SubmittedDate>
        <FeedProcessingStatus>_SUBMITTED_</FeedProcessingStatus>
    </FeedSubmissionInfo>
</SubmitFeedResult>
<ResponseMetadata>
    <RequestId>'.uniqid().'</RequestId>
</ResponseMetadata>
</SubmitFeedResponse>';

    header("X-Mws-Request-Id: " . uniqid());
    header("X-Amz-Request-Id: " . uniqid());
    header("X-Mws-Response-Context: default");
    header("X-Mws-Timestamp: " . date(DATE_ISO8601));
    header("Content-MD5: " . base64_encode(md5($content, true)));
    echo $content;
} catch (Exception $e) {
    exit('Failed to create feed: ' . $e->getMessage());
}

