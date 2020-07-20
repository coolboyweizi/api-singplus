<?php

namespace SingPlus\Domains\TUDC\Services;

use LogTUDC;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use SingPlus\Support\Helpers\Str;
use SingPlus\Contracts\TUDC\Services\GatewayService as GatewayServiceContract;
use SingPlus\Exceptions\AppException;

class GatewayService implements GatewayServiceContract
{
  /**
   * @var string
   */
  private $appId;

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

  /**
   * @var array
   *
   * please to see config/tudc::channel
   */
  private $channels;

  /**
   * @var string
   */
  private $defaultChannel;

  /**
   * @var boolean
   */
  private $channelAreadyChosen = false;

  /**
   * @param ClientInterface $httpClient
   * @param array $conf
   */
  private function __construct(
    ClientInterface $httpClient, array $conf
  ) {
    $this->httpClient = $httpClient;
    $this->appId = $conf['appId'];
    $this->appKey = $conf['appKey'];
    $this->appSecret = $conf['appSecret'];
  }

  static public function channel(string $appChannel)
  {
    $conf = array_get(config('tudc.channels'), $appChannel);
    if ( ! $conf) {
      throw new AppException('tudc channel not exists');
    }

    return new static(app()->make(ClientInterface::class), $conf);
  }

  /**
   * Send a json request to TUDC
   *
   * @param string $method
   * @param string $url
   * @param array $querys
   * @param array $postData
   * @param ?string $userToken
   *
   * @return \stdClass
   */
  public function requestJson(
    string $method,
    string $url,
    array $querys = [],
    array $postData = [],
    ?string $userToken = null
  ) : \stdClass {
    $method = strtoupper($method);
    $requestId = Str::uuid();
    $headers = [
      'X-Afmobi-RequestId'  => $requestId,
      'Content-Type'        => 'application/json; charset=utf-8',
    ];
    if ($userToken) {
      $headers['X-Afmobi-Token'] = $userToken;
    }

    $requestBody = $postData ? json_encode($postData) : '';

    $querys['appId'] = $this->appId;
    $querys['appKey'] = $this->appKey;
    $querys['timestamp'] = time();
    $querys['sign'] = $this->genSign($method, $userToken, $requestBody, $querys);
    $url .= '?' . http_build_query($querys);

    $options = [
      RequestOptions::HEADERS => $headers,
      RequestOptions::BODY    => $requestBody,
      RequestOptions::CONNECT_TIMEOUT => 60,
      RequestOptions::TIMEOUT         => 60,
    ];
    $logContext = [
      'requestId'   => $requestId,
      'userToken'   => $userToken,
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
      throw new \Exception(sprintf('Request TUDC interface: %s error', $url));
    }

    // parse response
    if ($response->getStatusCode() != 200) {
      $logContext['httpCode'] = $response->getStatusCode();
      LogTUDC::error('request http failed', $logContext);
      throw new \Exception(sprintf('Request TUDC interface: %s error', $url));
    }

    $responseContent = (string) $response->getBody();
    $result = json_decode($responseContent);

    if ( ! $result) {
      $logContext['responseContent'] = $responseContent;
      LogTUDC::error('request format error', $logContext);
      throw new \Exception(sprintf('Request TUDC interface: %s response format error', $url));
    }

    $logContext['responseData'] = $result;
    if ($result->code == 0) {
      LogTUDC::debug('request success', $logContext);
    } else {
      LogTUDC::error('request logic error', $logContext);
    }

    return $result;
  }

  /**
   * Generate tudc sign
   *
   * @param string $method    http request method
   * @param ?string $userToken
   * @param string $requestBody
   * @param array $querys
   *
   * @return string
   */
  protected function genSign(
    string $method,
    ?string $userToken,
    ?string $requestBody = '',
    array $querys
  ) : string {
    unset($querys['sign']);
    ksort($querys);
    $queryString = http_build_query($querys);
    $originStr = $this->appSecret
                    . $method
                    . $userToken
                    . $requestBody
                    . $queryString;
    return strtoupper(md5($originStr));
  }
}
