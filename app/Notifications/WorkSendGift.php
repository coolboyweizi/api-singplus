<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/31
 * Time: 下午2:52
 */

namespace SingPlus\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\FCM\FCMMessage;
use SingPlus\Support\Notification\Notifiable;
use SingPlus\Contracts\Notifications\Constants\Notification as NotificationConstant;

class WorkSendGift extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    private $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->type = NotificationConstant::TYPE_GIFT_SEND_FOR_WORK;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['fcm'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  Notifiable  $notifiable
     */
    public function toFCM(Notifiable $notifiable)
    {
        return (new FCMMessage())
            ->notification([
                'title'         => NotificationConstant::getPushTitle(),
                'body'          => $this->renderBody($this->data),
                'click_action'  => 'MainActivity',
            ])
            ->data([
                'type'  => NotificationConstant::TYPE_GIFT_SEND_FOR_WORK,
            ]);
    }

    private function renderBody(array $data) : string
    {
        $res = view('notifications.userSendGift', $data);

        return trim($res->render());
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    public function getType() : ?string
    {
        return $this->type;
    }
}