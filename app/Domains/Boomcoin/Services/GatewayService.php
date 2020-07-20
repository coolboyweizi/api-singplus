<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午2:16
 */

namespace SingPlus\Domains\Boomcoin\Services;

use GuzzleHttp\RequestOptions;
use LogTUDC;
use SingPlus\Contracts\Boomcoin\Services\GatewayService as GatewayServiceContract;
use GuzzleHttp\ClientInterface;
use SingPlus\Support\Helpers\Str;


class GatewayService implements GatewayServiceContract
{

    /**
     * @var string
     */
    private $appKey;

    /**
     * @var string
     */
    private $appSecret;

    /**
     * @var ClientInterface
     */
    private $httpClient;


    private function __construct(ClientInterface $httpClient, array $conf)
    {
        $this->httpClient = $httpClient;
        $this->appKey = $conf['consumerKey'];
        $this->appSecret = $conf['consumerSecret'];
    }

    public static function channel(string $appChannel = 'singplus'){
        $conf = array_get(config('boomcoin.channels'), $appChannel);
        return new static(app()->make(ClientInterface::class), $conf);
    }

    /**
     * Send a json request to Boomcoin
     *
     * @param string $method
     * @param string $url
     * @param array $querys
     * @param array $postData
     * @return \stdClass
     */
    public function requestJson(
        string $method,
        string $url,
        array $querys = [],
        array $postData = []
    ): \stdClass
    {
        $method = strtoupper($method);
        $requestId = Str::uuid();
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
        ];

        $requestBody = $postData ? json_encode($postData) : '';

        $querys['consumer_key'] = $this->appKey;
        $querys['consumer_secret'] = $this->appSecret;
        $url .= '?' . http_build_query($querys);

        $options = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::BODY    => $requestBody,
            RequestOptions::CONNECT_TIMEOUT => 60,
            RequestOptions::TIMEOUT         => 60,
            RequestOptions::VERIFY  => false,
        ];
        $logContext = [
            'requestId'   => $requestId,
            'method'      => $method,
            'url'         => $url,
            'requestBody' => $requestBody,
        ];

        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (\Exception $ex) {
            $logContext['respError'] = $ex->getCode();
            $logContext['respErrMsg'] = $ex->getMessage();
            LogTUDC::error('request exception', $logContext);
            throw new \Exception(sprintf('Request Boomcoin Gateway interface: %s error', $url));
        }

        // parse response
        if ($response->getStatusCode() != 200) {
            $logContext['httpCode'] = $response->getStatusCode();
            LogTUDC::error('request http failed', $logContext);
            throw new \Exception(sprintf('Request Boomcoin Gateway interface: %s error', $url));
        }

        $responseContent = (string) $response->getBody();
        $result = json_decode($responseContent);

        if ( ! $result) {
            $logContext['responseContent'] = $responseContent;
            LogTUDC::error('request format error', $logContext);
            throw new \Exception(sprintf('Request Boomcoin Gateway interface: %s response format error', $url));
        }

        $logContext['responseData'] = $result;
        if ($result->responseCode == 0) {
            LogTUDC::debug('request success', $logContext);
        } else {
            LogTUDC::error('request logic error', $logContext);
        }

        return $result;
    }
}