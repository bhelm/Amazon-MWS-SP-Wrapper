<?php
namespace AmazonMWSSPWrapper\AmazonSP;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Configuration;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use Buzz\Client\Curl;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\NullLogger;
use Dotenv\Dotenv;

class SdkConfig {
    private static ?SellingPartnerSDK $sdk = null;
    private static ?AccessToken $accessToken = null;

    private static function init() {
        if(self::$sdk !== null) {
            return;
        }
        $factory = new Psr17Factory();
        $client = new Curl($factory);

        @mkdir("../logs", 0777, true);
        $logger = new Logger('name');
        // set to DEBUG if you want to see the requests to the api
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/sp-api-php.log', Logger::ERROR));
        $dotenv = Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();

        self::$sdk = SellingPartnerSDK::create( $client,
            $factory, $factory,
            Configuration::forIAMUser(
                $_ENV['CLIENT_ID'],
                $_ENV['CLIENT_SECRET'],
                $_ENV['ACCESS_KEY'],
                $_ENV['SECRET_KEY']
            ),
            $logger);
    }

    public static function getSdk() {
        if(!self::$sdk) {
            self::init();
        }
        return self::$sdk;
    }
    public static function getAccessToken() {
        if(!self::$accessToken) {
            self::$accessToken = self::getSdk()->oAuth()->exchangeRefreshToken($_ENV['REFRESH_TOKEN']);
        }
        return self::$accessToken;
    }
}
