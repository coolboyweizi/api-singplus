<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/9
 * Time: 下午2:14
 */

namespace SingPlus\Contracts\TXIM\Services;

interface ImService
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
                                 array $customizeData = []);

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
                                 array $customizeData = []);

    /**
     * @param string $senderId
     * @param string $receiver
     * @param string $msg
     * @param string $desc
     * @return mixed
     */
    public function sendSimpleMsg(string $senderId, string $receiver, string $msg, string $desc);

}