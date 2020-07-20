<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/9
 * Time: 下午2:28
 */

namespace SingPlus\Services;
use \SingPlus\Contracts\TXIM\Services\ImService as ImServiceContract;
use SingPlus\Exceptions\AppException;

class IMService
{

    private $imService;

    public function __construct(ImServiceContract $imService)
    {
        $this->imService = $imService;
    }

    /**
     * @param array $receptors
     * @param string $type
     * @param array $data
     * @param array $customizeData
     * @throws AppException
     */
    public function sendGroup(array $receptors,
                              string $type,
                              array $data,
                              array $customizeData = []){
        try {
            $this->imService->sendGroupMsg($receptors,$type,$data,$customizeData);
        }catch (\Exception $e){
            throw new AppException('sendGroupMsg failed');
        }
    }

    /**
     * @param string $topic
     * @param string $type
     * @param array $data
     * @param array $customizeData
     * @throws AppException
     */
    public function sendTopic(string $topic,
                              string $type,
                              array $data,
                              array $customizeData = [])
    {
        try {
            $this->imService->sendTopicMsg($topic, $type, $data, $customizeData );
        }catch (\Exception $e){
            throw new AppException('sendTopicMsg failed ');
        }
    }

    /**
     * @param string $senderId
     * @param string $receiverId
     * @param string $msg
     * @param string $desc
     * @throws AppException
     */
    public function sendSimpleMsg(string $senderId, string $receiverId, string $msg, string $desc){
        try {
            $this->imService->sendSimpleMsg($senderId, $receiverId, $msg, $desc );
        }catch (\Exception $e){
            throw new AppException('sendSimpleMsg failed ');
        }
    }
}