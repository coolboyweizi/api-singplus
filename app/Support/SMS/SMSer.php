<?php

/**
 * SMSer --> (new) PendingSMS --> (dispatch) SendQueuedMessage
 *  |  ^             |                           |
 *  |  |---<---------|------<--------------------| 
 *  V 
 * (delegate)
 * TransportManager -> (fetch) Transport
 *
 * How to use:
 * SMS::driver('infobip')
 *    ->to('13800138000')
 *    ->send('smss.verifications.register', ['code' => 'zzzz']);
 *
 * SMS::driver('infobip')
 *    ->to(['13800138000', '13800138001'])
 *    ->send('smss.verifications.register', ['code' => 'zzzz']);
 *
 * SMS::to('13800138000')
 *    ->send('smss.verifications.register', ['code' => 'zzzz']);
 *
 * SMS::balance()   // return \SingPlus\SMS\Balance
 */

namespace SingPlus\SMS;

use Illuminate\Contracts\View\Factory as ViewFactory;
use SingPlus\SMS\PendingSMS;
use SingPlus\SMS\Message;
use SingPlus\SMS\TransportManager;
use SingPlus\SMS\Transport\Transport;

class SMSer
{
  /**
   * @var SMSManager
   */
  //protected $transportManager;
  public $transportManager;

  /**
   * @var ViewFactory
   */
  protected $views;

  public function __construct(
    TransportManager $transportManager,
    ViewFactory $views
  ) {
    $this->transportManager = $transportManager;
    $this->views = $views;
  }

  /**
   * Send message through specified transport
   *
   * @param Message $message      message will be send
   * @param string $transport     transport name
   */
  public function send(Message $message, string $transport = null)
  {
    $config = config('sms');
    if (array_get($config, 'config.pretending')) {
      return; 
    }

    $globalFrom = array_get($config, 'from');
    $globalTo = array_get($config, 'to', []);
    if ( ! $message->getFrom() && $globalFrom) {
      $message->from($globalFrom);
    }
    if ($globalTo) {
      $message->to($globalTo);
    }
    $this->getTransport($transport)->send($message);
  }

  /**
   * Get backend transport
   *
   * @param string $transport
   *
   * @return Transport
   */
  public function getTransport(string $transport = null) : Transport
  {
    return $this->transportManager->driver($transport);
  }

  /**
   * Generate message body from view and view data
   *
   * @param string $view        view name
   * @param array $data         view data
   *
   * @return string             assembled message body
   */
  public function genMessageBodyFromView(string $view, array $data = []) : string
  {
    return $this->views->make($view, $data)->render();
  }

  public function __call($method, $parameters)
  {
    return (new PendingSMS($this))->$method(...$parameters);  
  }
}
