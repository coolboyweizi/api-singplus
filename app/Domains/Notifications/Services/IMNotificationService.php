<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/8
 * Time: 下午2:31
 */

namespace SingPlus\Domains\Notifications\Services;

use Carbon\Carbon;
use GeoIp2\WebService\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use SingPlus\Contracts\Notifications\Services\NotificationService as NotificationServiceContract;
use SingPlus\Jobs\IMSendGroupMsg;
use SingPlus\Jobs\IMSendTopicMsg;
use LogTXIM;

class IMNotificationService implements NotificationServiceContract
{

    /**
     * Send notification to user
     *
     * @param string $receptor
     * @param string $type
     * @param array $data
     */
    public function notifyToUser(string $receptor, string $type, array $data)
    {
        // TODO: Implement notifyToUser() method.
    }

    /**
     * Send notification to multi users
     *
     * @param array $receptors elements are receptor alias
     * @param string $type
     * @param array $data
     * @param array $customizeData field as below
     *                                - icon ?string
     *                                - content ?string     message content
     *                                - redirectTo ?string  redirect url
     */
    public function notifyToMultiUsers(
        array $receptors,
        string $type,
        array $data,
        array $customizeData = []
    )
    {
        $len = count($receptors);
        $step = 500;

        for ($i = 0; $i < $len;){
            $receivers = array_slice($receptors, $i, $step);
            $job = (new IMSendGroupMsg($receivers, $type, $data, $customizeData))->delay(1);
            dispatch($job);
            $i = $i + $step;
        }

    }

    /**
     * Send notification to topic
     *
     * @param string $topic
     * @param string $type
     * @param array $data
     * @param array $customizeData field as below
     *                                - icon ?string
     *                                - content ?string     message content
     *                                - redirectTo ?string  redirect url
     */
    public function notifyToTopic(
        string $topic,
        string $type,
        array $data = [],
        array $customizeData
    )
    {
        $job = (new IMSendTopicMsg($topic,$type, $data, $customizeData))->delay(2);
        dispatch($job);
        LogTXIM::debug('IMNotificationService notifyToTopic', ["imTopicType" => $type]);
    }
}