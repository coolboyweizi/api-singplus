<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/22
 * Time: 下午5:27
 */

namespace SingPlus\Services;
use Log;
use Cache;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Musics\Services\MusicService as MusicServiceContract;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\Works\Services\WorkTagService as WorkTagServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\Works\WorkTagNotExistsException;

class WorkTagService
{

    const TYPE_LATEST = 'latest';
    const TYPE_SELECTION = 'selection';

    /**
     * @var WorkTagServiceContract
     */
    private $workTagService;

    /**
     * @var StorageServiceContract
     */
    private $storageService;

    /**
     * @var UserProfileServiceContract
     */
    private $userProfileService;

    /**
     * @var WorkServiceContract
     */
    private $workService;

    /**
     * @var MusicServiceContract
     */
    private $musicService;

    public function __construct(WorkTagServiceContract $workTagService,
                                StorageServiceContract $storageService,
                                UserProfileServiceContract $userProfileService,
                                WorkServiceContract $workService,
                                MusicServiceContract $musicService)
    {
        $this->workTagService = $workTagService;
        $this->storageService = $storageService;
        $this->userProfileService = $userProfileService;
        $this->workService = $workService;
        $this->musicService = $musicService;
    }

    /**
     * @param string $str
     * @return Collection   elements are \stdClass
     *                      - title string the title of work tags
     *
     */
    public function searchWorkTags(string $str, bool $force):Collection{
        $cacheKey = sprintf('searchWorkTags:%s:work:tags', $str);
        $expireAfter = 30;

        if (!$force && $expireAfter && ($cacheData = Cache::get($cacheKey))){
            $data = $cacheData;
        }else {
            $data = $this->workTagService->searchWorkTags($str);
            if ($data->count()){
                Cache::put($cacheKey, $data, $expireAfter);
            }
        }

        return $data;
    }

    /**
     * update work's tags by workId
     * @param string $workId
     * @return mixed
     */
    public function updateWorkTags(string $workId){
        return $this->workTagService->updateWorkTags($workId);
    }

    /**
     * @param string $tagTitle
     * @return \stdClass
     *              - title string
     *              - cover string
     *              - desc  string|null
     *              - joinCount int
     * @throws WorkTagNotExistsException
     */
    public function getWorkTagDetail(string $tagTitle) : \stdClass{
        $detail = $this->workTagService->getWorkTagDetail($tagTitle);
        if (!$detail){
            throw new WorkTagNotExistsException();
        }
        if ($detail->cover){
            $detail->cover = $this->storageService->toHttpUrl($detail->cover);
        }

        // 获取不区分大小写的标签的的参与次数
        $cacheKey = sprintf("workTag:joincount:%s", strtoupper($tagTitle));
        $cacheData = Cache::get($cacheKey);
        if ($cacheData == null){
            // 查询所有标签，更新参与次数缓存
            $cacheData = $this->workTagService->getWorkTagJoinCountWithoutCaseSensitive($tagTitle);
            $expired = 24*60*2;
            Cache::put($cacheKey, $cacheData,$expired);
        }
        $detail->joinCount = $cacheData;
        return $detail;
    }

    /**
     * @param string $workTag
     * @param null|string $workId
     * @param bool $isNext
     * @param int $size
     * @return Collection   elements are \stdClass
     *                  -id string  work id
     *                  -workId string  work id
     *                  -workName string work name
     *                  -user \stdclass
     *                      -userId
     *                      -avatar
     *                      -nickname
     *                  -music \stdClass
     *                      -musicId
     *                      -musicName
     *                  -cover  string
     *                  -chorusType int
     *                  -chorusCount int
     *                  -description desc
     *                  -resource
     *                  -listenCount
     *                  -favouriteCount
     *                  -commentCount
     *                  -transmitCount
     *                  -shareLink
     *                  -createdAt
     *
     */
    public function getTagWorkList(string $workTag, ?string $workId, bool $isNext, int $size, string $type):Collection{
        if ($type == WorkTagService::TYPE_SELECTION){
            return $this->getTagWorkSelection($workTag, $workId, $isNext, $size);
        }else if ($type == WorkTagService::TYPE_LATEST){
            return $this->getWorkTagLatestWorkList($workTag, $workId, $isNext, $size);
        }else {
            throw new AppException('type not allowed');
        }
    }

    /**
     * @param string $workTag
     * @param null|string $workId
     * @param bool $isNext
     * @param int $size
     * @return Collection   elements are \stdClass
     *                  -id string  work id
     *                  -workId string  work id
     *                  -workName string work name
     *                  -user \stdclass
     *                      -userId
     *                      -avatar
     *                      -nickname
     *                  -music \stdClass
     *                      -musicId
     *                      -musicName
     *                  -cover  string
     *                  -chorusType int
     *                  -chorusCount int
     *                  -description desc
     *                  -resource
     *                  -listenCount
     *                  -favouriteCount
     *                  -commentCount
     *                  -transmitCount
     *                  -shareLink
     *                  -createdAt
     *
     */
    private function getWorkTagLatestWorkList(string $workTag, ?string $workId, bool $isNext, int $size):Collection{
        $works = $this->workService->getTagWorksList($workTag, $workId, $isNext, $size);
        return $this->assembleTagWorks($works);
    }

    /**
     * @param string $workTag
     * @param null|string $workId
     * @param bool $isNext
     * @param int $size
     * @return Collection   elements are \stdClass
     *                  -id string  work id
     *                  -workId string  work id
     *                  -workName string work name
     *                  -user \stdclass
     *                      -userId
     *                      -avatar
     *                      -nickname
     *                  -music \stdClass
     *                      -musicId
     *                      -musicName
     *                  -cover  string
     *                  -chorusType int
     *                  -chorusCount int
     *                  -description desc
     *                  -resource
     *                  -listenCount
     *                  -favouriteCount
     *                  -commentCount
     *                  -transmitCount
     *                  -shareLink
     *                  -createdAt
     *
     */
    private function getTagWorkSelection(string $workTag, ?string $workId, bool $isNext, int $size):Collection{
        $workSelection = $this->workService->getTagWorkSelection($workTag, $workId, $isNext, $size);
        $workIds = [];
        $workSelection->each(function ($workSelection, $_) use (&$workIds) {
            $workIds[] = $workSelection->work_id;
        });
        $works = $this->workService->getWorksByIds($workIds);
        return $this->assembleTagWorks($works);
    }

    private function assembleTagWorks($works):Collection{
        $userIds = [];
        $musicIds = [];
        $works->each(function ($work, $_) use (&$userIds,&$musicIds) {
            $userIds[] = $work->userId;
            $musicIds[] = $work->musicId;
        });
        $users = $this->userProfileService->getUserProfiles(array_unique($userIds));
        $musics = $this->musicService->getMusics(array_unique($musicIds), true);
        return $works->map(function($work, $__) use ($users, $musics){
            $user = $users->where('userId', $work->userId)->first();
            if ( ! $user) {
                Log::alert('Data missed. work miss user profile', [
                    'work_id' => $work->workId,
                    'user_id' => $work->userId,
                ]);

                return null;
            }

            $music = $musics->where('musicId', $work->musicId)->first();
            if ( ! $music) {
                Log::alert('Data missed. work miss music', [
                    'work_id'   => $work->workId,
                    'music_id'  => $work->musicId,
                ]);

                return null;
            }

            $res = (object) [
                'workId'          => $work->workId,
                'workName'        => $work->workName,
                'user'            => (object) [
                    'userId'    => $user->userId,
                    'avatar'    => $this->storageService->toHttpUrl($user->avatar),
                    'nickname'  => $user->nickname,
                ],
                'music'           => (object) [
                    'musicId' => $music->musicId,
                    'name'    => $music->name,
                ],
                'cover'           => $this->storageService->toHttpUrl($work->cover),
                'chorusType'      => $work->chorusType,
                'chorusCount'     => $work->chorusCount,
                'description'     => $work->description,
                'resource'        => $this->storageService->toHttpUrl($work->resource),
                'listenCount'     => $work->listenCount,
                'favouriteCount'  => $work->favouriteCount,
                'commentCount'    => $work->commentCount,
                'transmitCount'   => $work->transmitCount,
                'shareLink'       => secure_url(sprintf('c/page/works/%s', $work->workId)),
                'createdAt'       => $work->createdAt,
            ];
            return $res;
        })->filter(function ($work, $_) {
            return ! is_null($work);
        });
    }
}