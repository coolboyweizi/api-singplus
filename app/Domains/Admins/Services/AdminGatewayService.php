<?php

namespace SingPlus\Domains\Admins\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Monolog\Logger;
use SingPlus\Support\Helpers\Str;

use SingPlus\Contracts\Admins\Services\AdminGatewayService as AdminGatewayServiceContract;

class AdminGatewayService implements AdminGatewayServiceContract
{
  const RESP_CODE_SUCCESS = 0;

  private static $SIGN_KEY_NAME = 'sign';

  /**
   * @var string 
   */
  private $signKey;

  /**
   * @var ClientInterface
   */
  private $httpClient;

  /**
   * @var Logger
   */
  private $logger;

  public function __construct(
    ClientInterface $httpClient,
    Logger $logger
  ) {
    $this->signKey = config('admin.signature');
    $this->httpClient = $httpClient;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function notifyWorkPublished(string $workId, string $authorId) : bool
  {
    $url = config('admin.endpoints.work_published_notification');
    $result = $this->post($url, [
      'userId'  => $authorId,
      'workId'  => $workId,
      'nonce'   => Str::quickRandom(10),
    ]);

    return $result->code == self::RESP_CODE_SUCCESS;
  }

  /**
   * {@inheritdoc}
   */
  public function notifyUpdateWorkRanking(string $musicId) : bool
  {
    $url = config('admin.endpoints.work_ranking_update_notification');
    $result = $this->post($url, [
      'musicId' => $musicId,
    ]);

    return $result->code == self::RESP_CODE_SUCCESS;
  }

  /**
   * {@inheritdoc}
   */
  public function notifyGenRecommendUserFollowing(string $userId) : bool
  {
    $url = config('admin.endpoints.friend_gen_recommend_user_following');
    $result = $this->post($url, [
      'userId'  => $userId,
    ]);

    return $result->code == self::RESP_CODE_SUCCESS;
  }

  /**
   * {@inheritdoc}
   */
  public function notifyUserFollowAction(string $userId, string $followedUserId) : bool
  {
    $url = config('admin.endpoints.friend_user_followed_others');
    $result = $this->post($url, [
      'userId'          => $userId,
      'subscribeUserId' => $followedUserId,
    ]);

    return $result->code == self::RESP_CODE_SUCCESS;
  }

  /**
  * Send a post request to merchant server
  *
  * @param string $url
  * @param array $data post request form data
  *
  * @return \stdClass
  */
  protected function post(string $url, array $data = []) : \stdClass
  {
    $data[self::$SIGN_KEY_NAME] = $this->sign($data);
    $options = [
      RequestOptions::FORM_PARAMS    => $data,
    ];

    return $this->rawRequestAndParseResponse('POST', $url, $options);
  }

  /**
   * Send a request
   *
   * @param string $method
   * @param string $url
   * @param array $options
   *
   * @return \stdClass
   */
  protected function rawRequestAndParseResponse(
      string $method,
      string $url,
      array $options = []
  ) : \stdClass {
    $options = array_merge([
      RequestOptions::VERSION         => '1.1',
      RequestOptions::HEADERS         => [
          'Accept'        => 'application/json',
      ],
      RequestOptions::CONNECT_TIMEOUT => 60,
      RequestOptions::TIMEOUT         => 60,
    ], $options);
    $response = $this->httpClient->request($method, $url, $options);
    if ($response->getStatusCode() != 200) {
      $this->logger->error('request failed', [
        'url'       => $url,
        'request'   => $options[RequestOptions::JSON],
        'respCode'  => $response->getStatusCode(),
      ]);
      throw new \Exception(sprintf('Request admin interface: %s error', $url));
    }

    $responseContent = (string) $response->getBody();
    $result = json_decode($responseContent);
    if ( ! $result) {
      $this->logger->error('Reponse format error', [
        'url'       => $url, 
        'request'   => isset($options[RequestOptions::FORM_PARAMS]) ? $options[RequestOptions::FORM_PARAMS] : [],
        'respBody'  => $responseContent,
      ]);
      throw new \Exception(sprintf('Reqeust admin interface: %s response format error', $url));
    }

    if ($result->code != self::RESP_CODE_SUCCESS) {
      $this->logger->error('Response logic error', [
        'url'       => $url,
        'request'   => isset($options[RequestOptions::FORM_PARAMS]) ? $options[RequestOptions::FORM_PARAMS] : [],
        'respBody'  => (array) $result,
      ]);
    } else {
      $this->logger->info('Request success', [
        'url'       => $url,
        'request'   => isset($options[RequestOptions::FORM_PARAMS]) ? $options[RequestOptions::FORM_PARAMS] : [],
        'task_id'   => $result->task_id, 
      ]);
    }

    return $result;
  }

  /**
   * Generate mac for data verification
   *
   * @param array $params
   */
  private function sign(array $params) : string
  {
    unset($params[self::$SIGN_KEY_NAME]);
    ksort($params);
    $plain = '';
    foreach ($params as $k => $v) {
      $plain .= $k . '=' . $v . '&';
    }
    $plain .= $this->signKey;

    return sha1($plain, false);
  }                                         
}
