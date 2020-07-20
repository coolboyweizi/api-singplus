<?php

namespace SingPlus\Domains\Verifications\Services;

use SMS;
use SingPlus\Contracts\Verifications\Services\VerificationService as VerificationServiceContract;
use SingPlus\Domains\Verifications\Repositories\VerificationRepository;
use SingPlus\Domains\Verifications\Models\Verification;
use SingPlus\Exceptions\Verifications\VerificationException;
use SingPlus\Exceptions\Verifications\VerificationFrequenceException;
use SingPlus\Support\Helpers\Str;

class VerificationService implements VerificationServiceContract
{
  const DEFAULT_EXPIRE_INTERVAL = 60;
  const DEFAULT_EXPIRED_AFTER = 600;

  /**
   * @var VerificationRepository
   */
  private $verificationRepo;

  /**
   * the minimum time interval (in seconds) after which message is allowd
   * to be sent since last. 0 for no limit.
   *
   * @var int
   */
  private $interval;

  /**
   * expiry time (in seconds)
   *
   * @var int
   */
  private $expiredAfter;

  /**
   * the maximum number of messages that can be sent within a period of time
   * max #. of messages,   0 for no limit
   *
   * @var int
   */
  private $limit;

  /**
   * message send limit period (in second)
   *
   * @var int
   */
  private $limitPeriod;

  /**
   * the total maximum number of messages that can be sent within a period of
   * time max #. of messages, 0 for no limit
   *
   * @var int
   */
  private $totalLimit;

  /**
   * message send total limit period (in second)
   *
   * @var int
   */
  private $totalLimitPeriod;

  public function __construct(
    VerificationRepository $verificationRepo,
    array $config
  ) {
    $this->verificationRepo = $verificationRepo;

    // rate limit defines two metrics
    // 1). the minimum time interval (in seconds) after which message is
    //     allowed to be sent since last
    $this->interval = (int) array_get($config, 'send_interval', self::DEFAULT_EXPIRE_INTERVAL);
    $this->expiredAfter = (int)array_get($config, 'expired_after', self::DEFAULT_EXPIRED_AFTER);

    // 2). the maximum number of messages that can be sent within a period of time
    $this->limit = (int) array_get($config, 'limit_count', 0);
    $this->limitPeriod = (int) array_get($config, 'limit_period', 0);

    // 3). the total maximum number of messages that can be sent within a period of time
    $this->totalLimit = (int) array_get($config, 'total_limit_count', 0);
    $this->totalLimitPeriod = (int) array_get($config, 'total_limit_period', 0);
  }

  /**
   * @see SingPlus\Contracts\Verifications\Services\VerificationService::verify()
   */
  public function verify(string $mobile, string $code)
  {
    $verification = $this->verificationRepo->findLastRequested($mobile);
    if ( ! $verification) { // no verification for this mobile, hack-ed?
      throw new VerificationException('capture code error');
    }

    // mark the verification code is used no matter this
    // verification passes or not
    $verification->delete();

    if ($verification->code != $code) {
      throw new VerificationException('capture code error');
    }                     
  }

  /**
   * @see SingPlus\Contracts\Verifications\Services\VerificationService::sendCode()
   */
  public function sendCode(string $mobile, string $sendMobile) : \stdClass
  {
    $this->checkSendLimitation($mobile);
    $code = $this->generateCodeAndStore($mobile);

    $pendingSMS = SMS::to($sendMobile);

    // message be sent to China should use specificed from
    if (preg_match("/^86/", $sendMobile)) {
      $pendingSMS->from(config('sms.from_for_china_message'));
    } elseif (preg_match("/^91/", $sendMobile)) {
      $pendingSMS->from(config('sms.from_for_india_message'));
    }

    $pendingSMS->send('smss.verification', [
      'code'          => $code,
      'expiredAfter'  => $this->expiredAfter,
    ]);

    return (object) [
      'code'      => $code,
      'interval'  => $this->interval,
    ];
  }

  /**
   * Check verification code send frequence
   */
  private function checkSendLimitation($mobile)
  {
    // rule#1. the next message cannot be sent within the specified time interval
    if ($this->interval > 0) {
      $count = $this->verificationRepo
                    ->countAfterTime($mobile, $this->timeAheadOf($this->interval));
      if ($count > 0) {
        throw new VerificationFrequenceException();
      }
    }

    // rule#2. message rate limit
    if ($this->limit > 0 && $this->limitPeriod > 0) {
      $count = $this->verificationRepo
                    ->countAfterTime($mobile, $this->timeAheadOf($this->limitPeriod));
      if ($count >= $this->limit) {
        throw new VerificationFrequenceException('send verification code exceed max limitaion');
      }
    }

    // rule#3. total rate limit
    if ($this->totalLimit > 0 && $this->totalLimitPeriod > 0) {
      $count = $this->verificationRepo
                    ->countAfterTime(null, $this->timeAheadOf($this->totalLimitPeriod));
      if ($count >= $this->totalLimit) {
        throw new VerificationFrequenceException('send verification code exceed max limitaion');
      }
    }
  }
  

  /**
   * Generate code and store in database
   */
  private function generateCodeAndStore(string $mobile) : string
  {
    // fetch last valid requested
    $verification = $this->verificationRepo->findLastRequested($mobile);
    $code = $verification ? $verification->code : $this->generateCode(4);
    
    $newVerification = (new Verification)->fill([
      'mobile'      => $mobile,
      'code'        => $code,
      'expired_at'  => date('Y-m-d H:i:s', strtotime(sprintf('%s seconds', $this->expiredAfter))),
    ]);
    $newVerification->save();

    return $code;
  }

  private function generateCode(int $length = 4) : string
  {
    return Str::quickRandom($length, '0123456789');
  }

  /**
   * Compute the time which goes ahead of given time($time) in seconds
   *
   * @param int $seconds seconds ahead
   * @param int $time    the time base, timestamp
   *
   * @return string  'Y-m-d H:i:s' format of the computed time
   */
  private function timeAheadOf(int $seconds, int $time = null) : string
  {
    return date('Y-m-d H:i:s', strtotime(sprintf('%s seconds ago', $seconds),
              $time ?: time()));
  }
}
