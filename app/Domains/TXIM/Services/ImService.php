<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/9
 * Time: 下午2:15
 */

namespace SingPlus\Domains\TXIM\Services;
use GuzzleHttp\Exception\TransferException;
use LogTXIM;
use GuzzleHttp\Client;
use SingPlus\Contracts\Notifications\Constants\Notification;
use SingPlus\Contracts\TXIM\Services\ImService as ServiceContract;
use SingPlus\Support\Helpers\Str;

class ImService implements ServiceContract
{


    /***
     * @param array $receptors
     * @param string $type
     * @param array $data
     * @param array $customizeData
     * @return mixed
     */
    public function sendGroupMsg(array $receptors,
                                 string $type,
                                 array $data,
                                 array $customizeData = [])
    {
        $cover = array_get($customizeData, 'cover');
        $icon = array_get($customizeData, 'icon');
        $content = array_get($customizeData, 'content');
        $redirectTo = array_get($customizeData, 'redirectTo');
        $title = array_get($customizeData, 'title');
        $comments = array_get($customizeData, 'comments');
        $requestId = Str::uuid();

        $client = new Client([
            'base_uri'  => config('txim.baseUrl')
        ]);

        $params = [
            'sender' => $this->getSenderId($type),
            'receiver' => $receptors,
            'msg_type' => 'TIMCustomElem',
            'msg' => [
                'Data' => [
                    'type' => 'notification',
                    'notification' => [
                        'logo' => $cover ? $cover : "",
                        'desc' => $comments ? $comments : $content,
                        'title' => $title,
                        'type' => $type,
                        'targetUrl' => $redirectTo
                    ],
                ],
                'Desc'  => $comments ? $comments : $content,
                'Ext' => ''
            ]
        ];

        $logContext = [
            'requestId'   => $requestId,
            'method'      => 'POST',
            'url'         => config('txim.baseUrl').'/sendgroup',
            'requestBody' => json_encode($params),
        ];

        try
        {
            $response = $client->request('POST', '/sendgroup',[
                'json' => $params
            ]);

            $obj = json_decode($response->getBody());
            $code = $obj->code;
            $logContext['responseData'] = $obj;
            if ($code == 0){
                LogTXIM::debug('sendGroupMsg request success', $logContext);
            }else {
                //  发送失败
                LogTXIM::error('sendGroupMsg request failed', $logContext);
                throw new \Exception(sprintf('failed sendGroupMsg request id %s', $requestId));
            }

        }catch (TransferException $e){
            // 异常处理
            $logContext['httpCode'] = $e->getCode();
            $logContext['httpError'] = $e->getMessage();
            LogTXIM::error('sendGroupMsg http error with exception ', $logContext);
            throw new \Exception(sprintf('exception sendGroupMsg request id %s', $requestId));
        }
    }

    /***
     * @param string $topic
     * @param string $type
     * @param array $data
     * @param array $customizeData
     * @return mixed
     */
    public function sendTopicMsg(string $topic,
                                 string $type,
                                 array $data,
                                 array $customizeData = [])
    {
        LogTXIM::debug('ImService sendTopicMsg  before', [$type]);

        $cover = array_get($customizeData, 'cover');
        $icon = array_get($customizeData, 'icon');
        $content = array_get($customizeData, 'content');
        $redirectTo = array_get($customizeData, 'redirectTo');
        $comments = array_get($customizeData, 'comments');
        $title = array_get($customizeData, 'title');
        $requestId = Str::uuid();


        $params = [
            'sender' => $this->getSenderId($type),
            'msg_type' => 'TIMCustomElem',
            'condition' => [
                'AttrsAnd' => [
                    'topic' => $topic
                ]
            ],
            'msg' => [
                'Data' => [
                    'type' => 'notification',
                    'notification' => [
                        'logo' => $cover ? $cover : "",
                        'desc' => $comments ? $comments : $content,
                        'title' => $title,
                        'type' => $type,
                        'targetUrl' => $redirectTo
                    ]
                ],
                'Desc'  => $comments ? $comments : $content,
                'Ext' => ''
            ]
        ];

        $logContext = [
            'requestId'   => $requestId,
            'method'      => 'POST',
            'url'         => config('txim.baseUrl').'/pushmsg',
            'requestBody' => json_encode($params),
        ];

        try
        {
            $client = new Client([
                'base_uri'  => config('txim.baseUrl')
            ]);
            $response = $client->request('POST', '/pushmsg',[
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $params
            ]);

            $obj = json_decode($response->getBody());
            $code = $obj->code;
            $logContext['responseData'] = $obj;
            if ($code == 0){
                LogTXIM::debug('sendTopicMsg request success', $logContext);
            }else {
                LogTXIM::error('sendTopicMsg request failed', $logContext);
                throw new \Exception(sprintf('failed sendTopicMsg request id %s', $requestId));
            }

        }catch (TransferException $e){
            // 异常处理
            $logContext['httpCode'] = $e->getCode();
            $logContext['httpError'] = $e->getMessage();
            LogTXIM::error('sendTopicMsg http error with exception ', $logContext);
            throw new \Exception(sprintf('exception sendTopicMsg request id %s', $requestId));
        }
    }

    /**
     * @param string $type
     * @return string
     */
    private function getSenderId(string $type) : string
    {

        if ($type == Notification::TOPIC_TYPE_ANNOUNCEMENT){
            return config('txim.senders.Annoucements');
        }else if ($type == Notification::TOPIC_TYPE_ACTIVITY){
            return config('txim.senders.Contests');
        }else {
            return config('txim.senders.EditorPicks');
        }
    }

    /**
     * @param string $senderId
     * @param string $receiver
     * @param string $msg
     * @param string $desc
     * @return mixed
     */
    public function sendSimpleMsg(string $senderId, string $receiver, string $msg, string $desc)
    {
        $params = [
            'sender' => $senderId,
            'receiver' => $receiver,
            'msg_type' => 'TIMTextElem',
            'msg' => [
                'Text' => $msg,
                'Desc'  => $msg,
            ]
        ];

        $requestId = Str::uuid();

        $logContext = [
            'requestId'   => $requestId,
            'method'      => 'POST',
            'url'         => config('txim.baseUrl').'/sendmsg',
            'requestBody' => json_encode($params),
        ];

        try
        {
            $client = new Client([
                'base_uri'  => config('txim.baseUrl')
            ]);
            $response = $client->request('POST', '/sendmsg',[
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $params
            ]);

            $obj = json_decode($response->getBody());
            $code = $obj->code;
            $logContext['responseData'] = $obj;
            if ($code == 0){
                LogTXIM::debug('sendTopicMsg request success', $logContext);
            }else {
                LogTXIM::error('sendTopicMsg request failed', $logContext);
                throw new \Exception(sprintf('failed sendTopicMsg request id %s', $requestId));
            }

        }catch (TransferException $e){
            // 异常处理
            $logContext['httpCode'] = $e->getCode();
            $logContext['httpError'] = $e->getMessage();
            LogTXIM::error('sendTopicMsg http error with exception ', $logContext);
            throw new \Exception(sprintf('exception sendTopicMsg request id %s', $requestId));
        }
    }
}