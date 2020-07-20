<?php

namespace SingPlus\SMS\Transport;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use infobip\api\model\Destination as InfobipDestination;
use infobip\api\model\sms\mt\send\Message as InfobipMessage;
use infobip\api\model\sms\mt\send\textual\SMSAdvancedTextualRequest;
use infobip\api\model\sms\mt\send\SMSResponse;
use infobip\api\client\SendMultipleTextualSmsAdvanced;
use infobip\api\configuration\Configuration;
use SingPlus\SMS\Transport\Transport;
use SingPlus\SMS\Message;
use SingPlus\SMS\Balance;
use SingPlus\SMS\Logs\Mongodb\SendLog;

class InfobipTransport extends Transport
{
  /**
   * @var infobip\api\configuration\Configuration
   */
  private $authConf;

  /**
   * infobip will push delivery info to this url, after sms be send
   *
   * @var string
   */
  private $notifyUrl;

  /**
   * @var array infobip\api\client\AbstractApiClient
   */
  private $clients;

  public function __construct(Application $app, string $transport, Configuration $authConf, ?string $notifyUrl)
  {
    parent::__construct($app, $transport);

    $this->authConf = $authConf;
    $this->notifyUrl = $notifyUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function send(Message $message, Collection &$failedRecipients = null) : int
  {
    $destinations = [];
    foreach ($message->getTo() as $to) {
      $destination = new InfobipDestination();
      $destination->setTo($to);
      $destinations[] = $destination;
    }
    $infobipMessage = (new InfobipMessage());
    $infobipMessage->setFrom($message->getFrom());
    $infobipMessage->setDestinations($destinations);
    $infobipMessage->setText($message->getBody());
    if ($this->notifyUrl) {
      $infobipMessage->setNotifyUrl($this->notifyUrl);
    }

    $requestBody = new SMSAdvancedTextualRequest();
    $requestBody->setMessages([$infobipMessage]);

    $client = $this->getClient('SendMultipleTextualSmsAdvanced');

    $response = $client->execute($requestBody);
    return $this->parseSendResponse($response, $message);
  }

  /**
   * {@inheritdoc}
   */
  public function balance() : Balance
  {
    $accountBalance = $this->getClient('GetAccountBalance')
                           ->execute();
    return new Balance(
                  $this->transport,
                  (string) $accountBalance->getBalance(),
                  $accountBalance->getCurrency()
               );
  }

  private function getClient(string $client)
  {
    if ( ! isset($this->clients[$client])) {
      $clientClass = sprintf('\infobip\api\client\%s', $client);
      $this->clients[$client] = new $clientClass($this->authConf);
    }

    return $this->clients[$client];
  }

  protected function parseSendResponse(
    SMSResponse $response,
    Message $sendMessage
  ) {
    $bulkId = $response->getBulkId();
    $messages = $response->getMessages();
    foreach ($messages as $message) {
      $messageId = $message->getMessageId();
      $to = $message->getTo();
      $smsCount = $message->getSmsCount();
      $statusDetail = $message->getStatus();
      $status = $this->parseResponseStatus($statusDetail->getGroupId());

      $log = new SendLog([
        'transport'     => $this->transport,
        'bulk_id'       => $bulkId,
        'message_id'    => $messageId,
        'from'          => $sendMessage->getFrom(),
        'to'            => $to,
        'message'       => $sendMessage->getBody(),
        'sms_count'     => $smsCount,
        'status'        => $status,
        'status_detail' => json_decode(json_encode($statusDetail)),
      ]);
      $log->save();
    }

    return 1;
  }

  protected function parseResponseStatus($statusGroupId) : string
  {
    $map = [
      '0'   => SendLog::STATUS_ACCEPTED,
      '1'   => SendLog::STATUS_PENDING,
      '2'   => SendLog::STATUS_UNDELIVERABLE,
      '3'   => SendLog::STATUS_DELIVERED,
      '4'   => SendLog::STATUS_EXPIRED,
      '5'   => SendLog::STATUS_REJECTED,
    ];

    return isset($map[$statusGroupId]) ? $map[$statusGroupId] : 'Unkown';
  }
}
