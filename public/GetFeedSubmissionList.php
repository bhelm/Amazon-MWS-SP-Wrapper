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

$feedSubmissionIds = [];
foreach ($queryParams as $key => $value) {
    if (preg_match('/FeedSubmissionIdList_Id_\d+/', $key)) {
        $feedSubmissionIds[] = $value;
    }
}

$responses = [];

foreach($feedSubmissionIds as $feedSubmissionId) {
    try {
        $response = $sdk->feeds()->getFeed($accessToken, Regions::EUROPE, $feedSubmissionId);

        $responses[] = '<FeedSubmissionInfo>
      <FeedProcessingStatus>_'.$response->getProcessingStatus().'_</FeedProcessingStatus>
      <FeedType>_'.$response->getFeedType().'_</FeedType>
      <StartedProcessingDate>'.$response->getProcessingStartTime()->format('Y-m-d\TH:i:sP').'</StartedProcessingDate>
      <FeedSubmissionId>'.$feedSubmissionId.'</FeedSubmissionId>
      <SubmittedDate>'.$response->getCreatedTime()->format('Y-m-d\TH:i:sP').'</SubmittedDate>
    </FeedSubmissionInfo>';
    } catch (Exception $e) {
        echo "could not fetch feed: ".$e->getMessage().PHP_EOL;
    }
}
$content = '<?xml version="1.0"?>
<GetFeedSubmissionListResponse xmlns="http://mws.amazonaws.com/doc/2009-01-01/">
  <GetFeedSubmissionListResult>
    <HasNext>false</HasNext>
    '.implode($responses).'
  </GetFeedSubmissionListResult>
  <ResponseMetadata>
    <RequestId>'.uniqid().'</RequestId>
  </ResponseMetadata>
</GetFeedSubmissionListResponse>'.PHP_EOL;

header("X-Mws-Request-Id: " . uniqid());
header("X-Amz-Request-Id: " . uniqid());
header("X-Mws-Response-Context: default");
header("X-Mws-Timestamp: " . date(DATE_ISO8601));
header("Content-MD5: " . base64_encode(md5($content, true)));
echo $content;
