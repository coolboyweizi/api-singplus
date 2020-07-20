<?php

namespace SingPlus\Listeners\Notifications;

use LogNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Contracts\Supports\Constants\Queue as QueueConstant;

class LogNotificationSent implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to
     *
     * @var string|null
     */
    public $queue = QueueConstant::CHANNEL_API_PUSH;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NotificationSent  $event
     * @return void
     */
    public function handle(NotificationSent $event)
    {
        // We can access properites of event as below:
        // notifiable, notification, channel, response
        $context = [
          'taskId'  => object_get($event->notification, 'taskId'),
          'channel' => $event->channel,
          'type'    => $event->notification->getType(),
          'tokens'  => $event->notifiable->getTarget(),
          'detail'  => $event->notification->toArray($event->notifiable),
        ];
        if ($event->response instanceof \LaravelFCM\Response\DownstreamResponseContract) {
          $context['numberSuccess'] = $event->response->numberSuccess();
          $context['numberFailure'] = $event->response->numberFailure();
          $context['numberModification'] = $event->response->numberModification();
          $context['tokensToRetry'] = $event->response->tokensToRetry();
          $context['tokensWithError'] = $event->response->tokensWithError();
          $context['tokensToDelete'] = $event->response->tokensToDelete();
          $context['tokensToModify'] = $event->response->tokensToModify();
        } elseif ($event->response instanceof \LaravelFCM\Response\TopicResponseContract) {
          $context['isSuccess'] = $event->response->isSuccess();
          $context['error'] = $event->response->error();
          $context['shouldRetry'] = $event->response->shouldRetry();
        }

        LogNotification::info('notification sent', $context);
    }
}
