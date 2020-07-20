<?php

namespace SingPlus\Domains\Works\Services;

use Carbon\Carbon;
use Cache;
use Illuminate\Support\Collection;
use SingPlus\Exceptions\Works\ModifyActionForbiddenException;
use SingPlus\Exceptions\Works\WorkSetPrivateActionForBidden;
use SingPlus\Exceptions\Works\WorkSetPrivateActionForBiddenException;
use SingPlus\Jobs\UpdateWorkPopularity;
use SingPlus\Support\Database\SeqCounter;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\Works\Constants\WorkConstant;
use SingPlus\Domains\Works\Models\Work;
use SingPlus\Domains\Works\Models\WorkUploadTask;
use SingPlus\Domains\Works\Models\Comment;
use SingPlus\Domains\Works\Models\WorkFavourite;
use SingPlus\Domains\Works\Models\MusicWorkRankingList;
use SingPlus\Domains\Works\Repositories\WorkSelectionRepository;
use SingPlus\Domains\Works\Repositories\H5WorkSelectionRepository;
use SingPlus\Domains\Works\Repositories\WorkRepository;
use SingPlus\Domains\Works\Repositories\CommentRepository;
use SingPlus\Domains\Works\Repositories\WorkFavouriteRepository;
use SingPlus\Domains\Works\Repositories\WorkUploadTaskRepository;
use SingPlus\Domains\Works\Repositories\MusicWorkRankingListRepository;
use SingPlus\Domains\Works\Repositories\RecommendWorkSheetRepository;
use SingPlus\Domains\Works\Repositories\TagWorkSelectionRepository;
use SingPlus\Exceptions\Works\WorkNotExistsException;
use SingPlus\Exceptions\Works\WorkChorusAccompanimentPrepareException;
use SingPlus\Exceptions\Works\CommentNotExistsException;

class WorkService implements WorkServiceContract
{
  /**
   * @var WorkSelectionRepository
   */
  private $workSelectionRepo;

  /**
   * @var H5WorkSelectionRepository
   */
  private $h5WorkSelectionRepo;

  /**
   * @var WorkRepository
   */
  private $workRepo;

  /**
   * @var CommentRepository
   */
  private $commentRepo;

  /**
   * @var WorkFavouriteRepository
   */
  private $workFavouriteRepo;

  /**
   * @var WorkUploadTaskRepository
   */
  private $workUploadTaskRepo;

  /**
   * @var MusicWorkRankingListRepository
   */
  private $workRankingListRepo;

  /**
   * @var RecommendWorkSheetRepository
   */
  private $recommendWorkSheet;

  /**
   * @var TagWorkSelectionRepository
   */
  private $tagWorkSelectionRepo;

  public function __construct(
    WorkRepository $workRepo,
    WorkSelectionRepository $workSelectionRepo,
    H5WorkSelectionRepository $h5WorkSelectionRepo,
    CommentRepository $commentRepo,
    WorkFavouriteRepository $workFavouriteRepo,
    WorkUploadTaskRepository $workUploadTaskRepo,
    MusicWorkRankingListRepository $workRankingListRepo,
    RecommendWorkSheetRepository $recommendWorkSheetRepo,
    TagWorkSelectionRepository $tagWorkSelectionRepository
  ) {
    $this->workRepo = $workRepo;
    $this->workSelectionRepo = $workSelectionRepo;
    $this->h5WorkSelectionRepo = $h5WorkSelectionRepo;
    $this->commentRepo = $commentRepo;
    $this->workFavouriteRepo = $workFavouriteRepo;
    $this->workUploadTaskRepo = $workUploadTaskRepo;
    $this->workRankingListRepo = $workRankingListRepo;
    $this->recommendWorkSheetRepo = $recommendWorkSheetRepo;
    $this->tagWorkSelectionRepo = $tagWorkSelectionRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function createTwoStepUploadTask(
    string $userId,
    string $musicId,
    ?string $workName,
    string $cover,
    bool $isDefaultCover,
    array $slides,
    int $duration,
    string $description,
    bool $noAccompaniment,
    ?string $resource = null,
    bool $isPrivate = false,
    ?int $chorusType,
    ?string $originWorkId
  ) : \stdClass {
    $task = new WorkUploadTask([
      'user_id'       => $userId,
      'music_id'      => $musicId,
      'name'          => $workName,
      'cover'         => $cover,
      'is_default_cover'  => $isDefaultCover ?
                                WorkUploadTask::DEFAULT_COVER_YES :
                                WorkUploadTask::DEFAULT_COVER_NO,
      'slides'        => $slides,
      'duration'      => $duration,
      'description'   => $description,
      'no_accompaniment'  => $noAccompaniment ?
                                WorkUploadTask::NO_ACCOMPANIMENT_YES :
                                WorkUploadTask::NO_ACCOMPANIMENT_NO,
      'is_private'    => $isPrivate ?
                                WorkUploadTask::IS_PRIVATE_YES :
                                WorkUploadTask::IS_PRIVATE_NO,
      'status'        => WorkUploadTask::STATUS_NORMAL,
    ]);
    if ($resource) {
      $task->resource = $resource;
    }
    if ( ! is_null($chorusType)) {
      $task->chorus_type = $chorusType;
      if ($chorusType == WorkConstant::CHORUS_TYPE_JOIN) {
        $originWork = $this->workRepo->findOneById($originWorkId, ['chorus_type']);
        if (
          ! $originWorkId || ! $originWork ||
          object_get($originWork, 'chorus_type') != WorkConstant::CHORUS_TYPE_START
        ) { 
          throw new WorkNotExistsException('origin chorus work not exists');
        }
        $task->origin_work_id = $originWorkId;
      }
    }
    $task->save();

    return (object) [
      'taskId'  => $task->id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getUserUploadTask(string $userId, string $taskId) : ?\stdClass
  {
    $task = $this->workUploadTaskRepo->findOneById($taskId);

    return ($task && $task->user_id == $userId)
              ? (object) [
                'musicId'     => $task->music_id,
                'workName'    => isset($task->name) ? $task->name : null,
                'duration'    => $task->duration,
                'cover'       => $task->cover,
                'slides'      => $task->slides,
                'description' => $task->description,
                'resource'    => isset($task->resource) ? $task->resource : null,
                'noAccompaniment' => $task->noAccompaniment(),
                'isDefaultCover'  => $task->isDefaultCover(),
                'isPrivate'   => $task->isPrivate(),
                'chorusType'  => isset($task->chorus_type) ? (int) $task->chorus_type : null,
                'originWorkId'  => object_get($task, 'origin_work_id'),
              ] : null;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTaskAfterUploaded(string $taskId)
  {
    $this->workUploadTaskRepo->deleteById($taskId);
  }

  /**
   * {@inheritdoc}
   */
  public function clearExpiredUploadTask(Carbon $expiredTime)
  {
    $this->workUploadTaskRepo->deleteAllByExpiredTime($expiredTime);
  }

  /**
   * {@inheritdoc}
   */
  public function addUserWork(
    string $userId,
    string $musicId,
    ?string $workName,
    string $uri,
    string $cover,
    bool $isDefaultCover,
    array $slides,
    int $duration,
    string $description,
    bool $noAccompaniment,
    bool $isPrivate = false,
    ?int $chorusType = null,
    ?string $originWorkId = null,
    ?string $countryAbbr = null
  ) : \stdClass
  {
    $work = new Work([
      'user_id'       => $userId,
      'music_id'      => $musicId,
      'name'          => $workName,
      'resource'      => $uri,
      'cover'         => $cover,
      'is_default_cover'  => $isDefaultCover ?
                              Work::DEFAULT_COVER_YES :
                              Work::DEFAULT_COVER_NO,
      'slides'        => $slides,
      'duration'      => $duration,
      'description'   => $description,
      'listen_count'  => 0,
      'favourite_count' => 0,
      'comment_count'   => 0,
      'transmit_count'  => 0,
      'no_accompaniment'  => $noAccompaniment ?
                              Work::NO_ACCOMPANIMENT_YES :
                              Work::NO_ACCOMPANIMENT_NO,
      'is_private'        => $isPrivate ?
                              Work::IS_PRIVATE_YES :
                              Work::IS_PRIVATE_NO,
      'display_order' => SeqCounter::getNext('works'),
      'status'        => Work::STATUS_NORMAL,
      'country_abbr'  => $countryAbbr,
    ]);
    if ( ! is_null($chorusType)) {
      $work->chorus_type = $chorusType;
    }
    if ($chorusType == WorkConstant::CHORUS_TYPE_JOIN) {
        $work->chorus_join_info = [
          'origin_work_id'  => $originWorkId,
        ];
    }
    // 合唱发起的作品，发布后，后台异步为其合成发起合唱伴奏，因此
    // 状态为伴奏准备中。后台完成合成后，会将状态置为normal
    if ($chorusType == WorkConstant::CHORUS_TYPE_START) {
      $work->status = Work::STATUS_CHORUS_ACCOMPANIMENT_PREPARE;
      $work->chorus_start_info = [
        'chorus_count'  => 0,
      ];
    }

    $work->save();

    return (object) [
      'workId'  => $work->id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSelections(
    ?string $selectionId,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection {
    $displayOrder = null;
    if ($selectionId) {
      $selection = $this->workSelectionRepo->findOneById($selectionId);
      $displayOrder = $selection ? $selection->display_order : null;
    }
    $selections = $this->workSelectionRepo
                       ->findAllForPagination($displayOrder, $isNext, $size, $countryAbbr);

    $workIds = $selections->map(function ($selection, $_) {
                      return $selection->work_id;
                    })->toArray();
    
    $works = $this->workRepo->findAllByIds($workIds);
    if ($works->count() == 0) {
      return collect();
    }

    $res = collect();
    $selections->each(function ($select, $_) use ($works, $res) {
      $work = $works->where('id', $select->work_id)->first();
      if ($work) {
        $res->push((object) [
          'selectionId'   => $select->id,
          'workId'        => $work->id,
          'musicId'       => $work->music_id,
          'workName'      => isset($work->name) ? $work->name : null,
          'userId'        => $work->user_id,
          'listenCount'   => (int) $work->listen_count + $this->getWorkListenNum($work->id),
          'commentCount'    => (int) $work->comment_count,
          'favouriteCount'  => (int) $work->favourite_count,
          'transmitCount'   => (int) $work->transmit_count,
          'cover'         => $work->cover,
          'isDefaultCover'  => $work->isDefaultCover(),
          'slides'        => $work->slides ? $work->slides : [],
          'description'   => $work->description,
          'resource'      => $work->resource,
          'chorusType'    => object_get($work, 'chorus_type'),
          'chorusCount'   => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_START) && object_get($work, 'chorus_start_info') ?
                              (int) array_get($work->chorus_start_info, 'chorus_count', 0) : 0,
          'originWorkId'  => object_get($work, 'chorus_join_info') ?
                                array_get($work->chorus_join_info, 'origin_work_id') : null,
          'createdAt'     => $work->created_at,
          'workPopularity' => $work->popularity ? $work->popularity : 0,
          'giftAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_amount', 0): 0,
          'giftCoinAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_coin_amount', 0) : 0,
          'giftPopularity' => object_get($work, 'gift_info')  ? array_get($work->gift_info, 'gift_popularity_amount', 0) : 0,
        ]);
      }
    });

    return $res;
  }

  /**
   * {@inheritdoc}
   */
  public function getH5Selections(string $countryAbbr) : Collection
  {
    $selections = $this->h5WorkSelectionRepo->findAll($countryAbbr);

    $workIds = $selections->map(function ($selection, $_) {
                      return $selection->work_id;
                    })->toArray();
    
    $works = $this->workRepo->findAllByIds($workIds);
    if ($works->count() == 0) {
      return collect();
    }

    $res = collect();
    $selections->each(function ($select, $_) use ($works, $res) {
      $work = $works->where('id', $select->work_id)->first();
      if ($work) {
        $res->push((object) [
          'selectionId'   => $select->id,
          'workId'        => $work->id,
          'musicId'       => $work->music_id,
          'workName'      => isset($work->name) ? $work->name : null,
          'userId'        => $work->user_id,
          'listenCount'   => (int) $work->listen_count + $this->getWorkListenNum($work->id),
          'commentCount'    => (int) $work->comment_count,
          'favouriteCount'  => (int) $work->favourite_count,
          'transmitCount'   => (int) $work->transmit_count,
          'cover'         => $work->cover,
          'isDefaultCover'  => $work->isDefaultCover(),
          'slides'        => $work->slides ? $work->slides : [],
          'description'   => $work->description,
          'createdAt'     => $work->created_at,
          'workPopularity' => $work->popularity ? $work->popularity : 0,
          'giftAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_amount', 0): 0,
          'giftCoinAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_coin_amount', 0) : 0,
          'giftPopularity' => object_get($work, 'gift_info')  ? array_get($work->gift_info, 'gift_popularity_amount', 0) : 0,
        ]);
      }
    });

    return $res;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorks(?string $workId, bool $isNext, int $size) : Collection
  {
    $displayOrder = null;
    if ($workId) {
      $work = $this->workRepo->findOneById($workId, ['display_order']);
      $displayOrder = $work ? $work->display_order : null;
    }
    return $this->workRepo
                ->findAllForPagination($displayOrder, $isNext, $size)
                ->map(function ($work, $_) {
                  return (object) [
                    'workId'          => $work->id,
                    'userId'          => $work->user_id,
                    'musicId'         => $work->music_id,
                    'workName'        => isset($work->name) ? $work->name : null,
                    'cover'           => $work->cover,
                    'description'     => $work->description,
                    'listenCount'     => (int) $work->listen_count + $this->getWorkListenNum($work->id),
                    'commentCount'    => (int) $work->comment_count,
                    'favouriteCount'  => (int) $work->favourite_count,
                    'transmitCount'   => (int) $work->transmit_count,
                    'resource'        => $work->resource,
                    'createdAt'       => $work->created_at,
                    'chorusType'      => object_get($work, 'chorus_type'),
                    'chorusCount'     => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_START) && object_get($work, 'chorus_start_info') ?
                                        (int) array_get($work->chorus_start_info, 'chorus_count', 0) : 0,
                    'originWorkId'  => object_get($work, 'chorus_join_info') ?
                                array_get($work->chorus_join_info, 'origin_work_id') : null,
                    'workPopularity' => $work->popularity ? $work->popularity : 0,
                    'giftAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_amount', 0): 0,
                    'giftCoinAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_coin_amount', 0) : 0,
                    'giftPopularity' => object_get($work, 'gift_info')  ? array_get($work->gift_info, 'gift_popularity_amount', 0) : 0,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getUserWorks(
    string $invokeUserId,
    string $userId,
    ?string $workId,
    bool $isNext,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($workId) {
      $work = $this->workRepo->findOneById($workId, ['display_order']);
      $displayOrder = $work ? $work->display_order : null;
    }

    $includePrivate = false;
    if ($invokeUserId == $userId) {
      $includePrivate = true;
    }
    return $this->workRepo
                ->findAllByUserForPagination($userId, $displayOrder, $isNext, $size, $includePrivate)
                ->map(function ($work, $_) {
                  return (object) [
                    'workId'          => $work->id,
                    'userId'          => $work->user_id,
                    'musicId'         => $work->music_id,
                    'workName'        => isset($work->name) ? $work->name : null,
                    'cover'           => $work->cover,
                    'description'     => $work->description,
                    'listenCount'     => (int) $work->listen_count + $this->getWorkListenNum($work->id),
                    'commentCount'    => (int) $work->comment_count,
                    'favouriteCount'  => (int) $work->favourite_count,
                    'transmitCount'   => (int) $work->transmit_count,
                    'isPrivate'       => $work->isPrivate(),
                    'createdAt'       => $work->created_at,
                    'chorusType'      => object_get($work, 'chorus_type'),
                    'chorusCount'     => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_START) && object_get($work, 'chorus_start_info') ?
                                        (int) array_get($work->chorus_start_info, 'chorus_count', 0) : 0,
                    'originWorkId'  => object_get($work, 'chorus_join_info') ?
                                array_get($work->chorus_join_info, 'origin_work_id') : null,
                    'workPopularity' => $work->popularity ? $work->popularity : 0,
                    'giftAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_amount', 0): 0,
                    'giftCoinAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_coin_amount', 0) : 0,
                    'giftPopularity' => object_get($work, 'gift_info')  ? array_get($work->gift_info, 'gift_popularity_amount', 0) : 0,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getUserChorusStartWorks(
    string $invokeUserId,
    string $userId,
    ?string $workId,
    bool $isNext,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($workId) {
      $work = $this->workRepo->findOneById($workId, ['display_order']);
      $displayOrder = $work ? $work->display_order : null;
    }

    $includePrivate = false;
    if ($invokeUserId == $userId) {
      $includePrivate = true;
    }
    return $this->workRepo
                ->findAllChorusStartByUserForPagination(
                  $userId, $displayOrder, $isNext, $size, $includePrivate
                )
                ->map(function ($work, $_) {
                  return (object) [
                    'workId'          => $work->id,
                    'userId'          => $work->user_id,
                    'musicId'         => $work->music_id,
                    'workName'        => isset($work->name) ? $work->name : null,
                    'cover'           => $work->cover,
                    'isPrivate'       => $work->isPrivate(),
                    'createdAt'       => $work->created_at,
                    'chorusType'      => object_get($work, 'chorus_type'),
                    'chorusCount'     => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_START) && object_get($work, 'chorus_start_info') ?
                                        (int) array_get($work->chorus_start_info, 'chorus_count', 0) : 0,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getChorusJoinsOfChorusStart(
    string $originWorkId,
    ?string $workId,
    bool $isNext,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($workId) {
      $work = $this->workRepo->findOneById($workId, ['display_order']);
      $displayOrder = $work ? $work->display_order : null;
    }

    return $this->workRepo
                ->findAllChorusJoinByChorusStartWorkIdForPagination(
                  $originWorkId, $displayOrder, $isNext, $size
                )
                ->map(function ($work, $_) {
                  return (object) [
                    'workId'        => $work->id,
                    'userId'        => $work->user_id,
                    'musicId'       => $work->music_id,
                    'workName'      => $work->name,
                    'createdAt'     => $work->created_at,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getUsersWorks(
    array $userIds,
    ?string $id,
    bool $isNext,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($id) {
      $work = $this->workRepo->findOneById($id, ['display_order']);
      $displayOrder = $work ? $work->display_order : null;
    }
    return $this->workRepo
                ->findAllByUserIdsForPagination($userIds, $displayOrder, $isNext, $size)
                ->map(function ($work, $_) {
                  return (object) [
                    'id'              => $work->id,
                    'workId'          => $work->id,
                    'userId'          => $work->user_id,
                    'musicId'         => $work->music_id,
                    'workName'        => isset($work->name) ? $work->name : null,
                    'cover'           => $work->cover,
                    'description'     => $work->description,
                    'listenCount'     => (int) $work->listen_count + $this->getWorkListenNum($work->id),
                    'commentCount'    => (int) $work->comment_count,
                    'favouriteCount'  => (int) $work->favourite_count,
                    'transmitCount'   => (int) $work->transmit_count,
                    'resource'        => $work->resource,
                    'createdAt'       => $work->created_at,
                    'chorusType'      => object_get($work, 'chorus_type'),
                    'chorusCount'     => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_START) && object_get($work, 'chorus_start_info') ?
                                        (int) array_get($work->chorus_start_info, 'chorus_count', 0) : 0,
                    'originWorkId'  => object_get($work, 'chorus_join_info') ?
                                array_get($work->chorus_join_info, 'origin_work_id') : null,
                    'workPopularity' => $work->popularity ? $work->popularity : 0,
                    'giftAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_amount', 0): 0,
                    'giftCoinAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_coin_amount', 0) : 0,
                    'giftPopularity' => object_get($work, 'gift_info')  ? array_get($work->gift_info, 'gift_popularity_amount', 0) : 0,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function incrWorkListenCount(string $workId)
  {
    $work = $this->workRepo->findOneById($workId, ['user_id', 'status']);

    if ($work && $work->isNormal()) {
      $this->addWorkListenNum($work->id);
      $this->addUserWorkListenNum($work->user_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function incrWorkTransmitCount(string $workId)
  {
    return $this->workRepo->incrWorkTransmitCount($workId);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetail(
    string $workId,
    bool $disableCounter = false,
    bool $force = false
  ) : ?\stdClass {
    $fields = [
      'user_id', 'music_id', 'name', 'cover', 'slides', 'description',
      'listen_count', 'comment_count', 'favourite_count',
      'transmit_count', 'created_at', 'resource', 'duration', 'status',
      'no_accompaniment', 'chorus_type', 'chorus_start_info.chorus_count',
      'chorus_join_info.origin_work_id','gift_info','popularity','is_private'
    ];

    $work = $this->workRepo->findOneById($workId, $fields);

    if ($work && $work->isNormal() && ! $disableCounter) {
      $this->addWorkListenNum($work->id);
      $this->addUserWorkListenNum($work->user_id);
    }

    return (( ! $force && $work && $work->isNormal()) || ($force && $work)) ? (object) [
                      'workId'          => $work->id,
                      'userId'          => $work->user_id,
                      'musicId'         => $work->music_id,
                      'workName'        => isset($work->name) ? $work->name : null,
                      'cover'           => $work->cover,
                      'slides'          => collect($work->slides),
                      'description'     => $work->description,
                      'listenCount'     => (int) $work->listen_count + $this->getWorkListenNum($work->id),
                      'commentCount'    => (int) $work->comment_count,
                      'favouriteCount'  => (int) $work->favourite_count,
                      'transmitCount'   => (int) $work->transmit_count,
                      'createdAt'       => $work->created_at,
                      'resource'        => $work->resource,
                      'duration'        => (int) $work->duration,
                      'isNormal'        => $work->isNormal(),
                      'noAccompaniment' => $work->noAccompaniment(),
                      'isPrivate'       => $work->isPrivate(),
                      'chorusType'      => object_get($work, 'chorus_type'),
                      'chorusStartInfo'   => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_START) && object_get($work, 'chorus_start_info') ?
                              (object) [
                                'chorusCount' => array_get($work->chorus_start_info, 'chorus_count', 0),
                              ] : null,
                      'chorusJoinInfo'  => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_JOIN) && object_get($work, 'chorus_join_info') ?
                              (object) [
                                'originWorkId'  => array_get($work->chorus_join_info, 'origin_work_id'),
                              ] : null,
                      'workPopularity' => $work->popularity ? $work->popularity : 0,
                      'giftAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_amount', 0): 0,
                      'giftCoinAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_coin_amount', 0) : 0,
                      'giftPopularity' => object_get($work, 'gift_info')  ? array_get($work->gift_info, 'gift_popularity_amount', 0) : 0,
                    ] : null;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWork(string $workId)
  {
    $work = $this->workRepo->findOneById($workId, ['status']);
    if ($work) {
      $work->status = Work::STATUS_DELETED;
      $work->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function comment(
    string $authorId,
    string $content,
    string $workId,
    ?string $commentId,
    ?int $commentType,
    ?string $repliedId,
    string $giftFeedId = null
  ) : \stdClass {
    $work = $this->workRepo->findOneById($workId, ['user_id', 'comment_count']);
    if ( ! $work) {
      throw new WorkNotExistsException();
    }
    $repliedUserId = $work->user_id;
    if ($commentId) {
      $comment = $this->commentRepo->findOneById($commentId, ['author_id']);
      if ( ! $comment) {
        throw new CommentNotExistsException();
      }
      $repliedUserId = $comment->author_id;
    }

    if ($repliedId && $commentType && $commentType == Comment::TYPE_SEND_GIFT){
        $repliedUserId = $repliedId;
    }

    $comment = new Comment([
      'work_id'           => $workId,
      'comment_id'        => $commentId,
      'content'           => $content,
      'author_id'         => $authorId,
      'replied_user_id'   => $repliedUserId,
      'status'            => Comment::STATUS_NORMAL,
      'display_order'     => SeqCounter::getNext('comments'),
      'type'      => $commentType ? $commentType : Comment::TYPE_NORMAL,
      'gift_feed_id'  => $giftFeedId,
    ]);
    $comment->save();
    $work->comment_count = $work->comment_count + 1;
    $work->save();

    return (object) [
      'commentId' => $comment->id,
      'repliedUserId' => $repliedUserId,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getComment(string $commentId, bool $force = false) : ?\stdClass
  {
    $comment = $this->commentRepo->findOneById($commentId);

    return (($force && $comment) || ( ! $force && $comment && $comment->isNormal())) ? (object) [
      'commentId'         => $comment->id,
      'workId'            => $comment->work_id,
      'authorId'          => $comment->author_id,
      'repliedUserId'     => $comment->replied_user_id,
      'repliedCommentId'  => $comment->comment_id,
      'isNormal'          => $comment->isNormal(),
      'commentType'       => $comment->type ? $comment->type : Comment::TYPE_NORMAL,
      'content'           => $comment->content,
    ] : null;
  }

  /**
   * {@inheritdoc}
   */
  public function getComments(array $commentIds, bool $force = false) : Collection
  {
    if (empty($commentIds)) {
      return collect();
    }

    $comments = $this->commentRepo->findAllByIds($commentIds, $force);

    return $comments->map(function ($comment, $_) {
      return (object) [
        'commentId'         => $comment->id,
        'repliedCommentId'  => $comment->comment_id,
        'authorId'          => $comment->author_id,
        'musicId'           => $comment->work->music_id,
        'work'              => (object) [
                                  'workId'    => $comment->work->id,
                                  'workName'  => isset($comment->work->name) ?
                                                  $comment->work->name : null,
                                ],
        'repliedComment'    => $comment->repliedComment ?
                                  (object) [
                                    'commentId' => $comment->repliedComment->id,
                                    'content'   => $comment->repliedComment->content,
                                  ] : null,
        'content'           => $comment->content,
        'createdAt'         => $comment->created_at,
        'isNormal'          => $comment->isNormal(),
        'commentType'       => $comment->type ? $comment->type : Comment::TYPE_NORMAL,
        'giftFeedId'        => $comment->gift_feed_id,
      ];
    });
  }

  /**
   * {@inheritdoc}
   */
  public function deleteComment(string $commentId)
  {
    $comment = $this->commentRepo->findOneById($commentId);
    
    if ($comment) {
      $work = $this->workRepo->findOneById($comment->work_id, ['user_id', 'comment_count']);
      $work->comment_count = $work->comment_count - 1;
      $work->save();
      
      $comment->status = Comment::STATUS_DELETED;
      return $comment->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkComments(
    string $workId,
    ?string $commentId,
    bool $isNext,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($commentId) {
      $comment = $this->commentRepo->findOneById($commentId, ['display_order']);
      $displayOrder = $comment ? $comment->display_order : null;
    }
    
    return $this->commentRepo
                ->findWorkAllForPagination($workId, $displayOrder, $isNext, $size)
                ->map(function ($comment, $_) {
                  return (object) [
                    'commentId'     => $comment->id,
                    'repliedCommentId' => $comment->comment_id,
                    'workId'        => $comment->work_id,
                    'content'       => $comment->content,
                    'authorId'      => $comment->author_id,
                    'repliedUserId' => $comment->replied_user_id,
                    'createdAt'     => $comment->created_at,
                    'commentType'       => $comment->type ? $comment->type : Comment::TYPE_NORMAL,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getUploadedWork(string $clientId) : ?string
  {
    $work = $this->workRepo->findOneByClientId($clientId, ['_id']);

    return $work ? $work->id : null;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserRelatedComments(
    string $userId,
    ?string $commentId,
    bool $isNext,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($commentId) {
      $comment = $this->commentRepo->findOneById($commentId, ['display_order']);
      $displayOrder = $comment ? $comment->display_order : null;
    }
    return $this->commentRepo
                ->findAllUserRelatedForPagination(
                  $userId, $displayOrder, $isNext, $size
                )
                ->map(function ($comment, $_) {
                  return (object) [
                    'commentId'       => $comment->id,
                    'repliedCommentId' => $comment->comment_id,
                    'authorId'        => $comment->author_id,
                    'musicId'         => $comment->work->music_id,
                    'work'            => (object) [
                                            'workId'  => $comment->work->id
                                          ],
                    'repliedComment'  => $comment->repliedComment
                                            ? (object) [
                                                'commentId' => $comment->repliedComment->id,
                                                'content'   => $comment->repliedComment->content,
                                              ] : null,
                    'content'         => $comment->content,
                    'createdAt'       => $comment->created_at,
                    'commentType'       => $comment->type ? $comment->type : Comment::TYPE_NORMAL,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function favouriteWork(string $userId, string $workId) : \stdClass
  {
    // check workId
    $work = $this->workRepo->findOneById($workId, ['_id','user_id']);
    if ( ! $work) {
      throw new WorkNotExistsException();
    }

    $increments = 1;
    // handle favourite record
    $favourite = $this->workFavouriteRepo->findOneByWorkIdAndUserId($workId, $userId, true);
    $isCanceled = false;
    if ($favourite) {
      // if exists, cancel this favourite
      if ($favourite->trashed()) {
        $favourite->restore();
        $isCanceled = true;
      } else {
        $increments = -1;
        $favourite->delete();
      }
    } else {
      // if not exists, create this favourite
      $favourite = new WorkFavourite([
                          'work_id' => $workId,
                          'user_id' => $userId,
                        ]);
      $favourite->save();
    }

    // update work.favouriteCount field
    $this->workRepo->updateWorkFavouriteCount($workId, $increments);

    return (object) [
      'favouriteId' => $favourite->id,
      'increments'  => $increments,
      'isCanceled'  => $isCanceled,
      'workUserId'  => $work->user_id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isFavourite(string $userId, string $workId) : bool
  {
    $favourite = $this->workFavouriteRepo->findOneByWorkIdAndUserId($workId, $userId);

    return $favourite ? true : false;
  }

  /**
   * {@inheritdoc}
   */
  public function getFavourite(string $favouriteId, bool $force = false) : ?\stdClass
  {
    $favourite = $this->workFavouriteRepo->findOneById($favouriteId, $force);

    return $favourite ? (object) [
                            'favouriteId'   => $favourite->id,
                            'userId'        => $favourite->user_id,
                            'workId'        => $favourite->work_id,
                            'isNormal'      => $favourite->trashed() ? false : true,
                          ] : null;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkFavourite(string $workId, ?string $id, bool $isNext, int $size) : Collection
  {
    $displayOrder = null;
    if ($id) {
      $favourite = $this->workFavouriteRepo->findOneById($id, true);
      $displayOrder = $favourite ? $favourite->updated_at->format('Y-m-d H:i:s') : null;
    }
    return $this->workFavouriteRepo
                ->findAllByWorkIdForPagination($workId, $displayOrder, $isNext, $size)
                ->map(function ($favourite) {
                  return (object) [
                    'favouriteId'   => $favourite->id,
                    'userId'        => $favourite->user_id,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getUserWorkListenNum(string $userId) : int
  {
    $key = $this->userWorkListenNumKey($userId);
    return (int) Cache::get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getWorksByIds(array $workIds, bool $withPrivate = false) : Collection
  {
    if (empty($workIds)) {
      return collect();
    }
    $works = $this->workRepo
                  ->findAllByIds($workIds, true, ['*'], $withPrivate);
    return collect($workIds)->map(function ($workId, $_) use($works)  {
        $work = $works->where('id', $workId)->first();
        if ( ! $work) {
            return null;
        }

        return (object) [
            'workId'          => $work->id,
            'userId'          => $work->user_id,
            'musicId'         => $work->music_id, 
            'cover'           => $work->cover,
            'workName'        => $work->name,
            'noAccompaniment' => $work->noAccompaniment(),
            'isNormal'        => $work->isNormal(),
            'listenCount'     => (int) $work->listen_count + $this->getWorkListenNum($work->id),
            'favouriteCount'  => $work->favourite_count,
            'commentCount'    => $work->comment_count,
            'description'     => $work->description,
            'transmitCount'   => (int) $work->transmit_count,
            'isPrivate'       => $work->isPrivate(),
            'resource'      => $work->resource,
            'chorusType'    => object_get($work, 'chorus_type'),
            'chorusCount'   => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_START) && object_get($work, 'chorus_start_info') ?
                      (int) array_get($work->chorus_start_info, 'chorus_count', 0) : 0,
            'originWorkId'  => object_get($work, 'chorus_join_info') ?
                        array_get($work->chorus_join_info, 'origin_work_id') : null,
            'createdAt'     => $work->created_at,
            'status'        => $work->status,
            'workPopularity' => $work->popularity ? $work->popularity : 0,
            'giftAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_amount', 0): 0,
            'giftCoinAmount' => object_get($work, 'gift_info') ? array_get($work->gift_info, 'gift_coin_amount', 0) : 0,
            'giftPopularity' => object_get($work, 'gift_info')  ? array_get($work->gift_info, 'gift_popularity_amount', 0) : 0,
        ];
    })
    ->filter(function ($work, $_) {
        return ! is_null($work);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getUserFavouriteStatusOfMultiWorks(string $userId, array $workIds) : array
  {
    $favourites = $this->workFavouriteRepo->findAllByWorkIdsAndUserId($userId, $workIds);

    $status = [];
    foreach ($workIds as $workId) {
      $favourite = $favourites->where('work_id', $workId)->first();
      $status[$workId] = $favourite ? true : false;
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getMusicSoloRankingList(string $musicId) : Collection
  {
    $rankingList = $this->workRankingListRepo
                        ->findAllMusicIdAndType($musicId, MusicWorkRankingList::TYPE_SOLO);

    // 如果排行榜为空，表示尚未生成，使用默认排行
    if ($rankingList->isEmpty()) {
      return $this->workRepo
                  ->findAllSoloByMusicId($musicId)
                  ->map(function ($work, $_) {
                    return (object) [
                      'workId'      => $work->id,
                      'userId'      => $work->user_id,
                      'listenCount' => (int) $work->listen_count + $this->getWorkListenNum($work->id),
                    ];
                  })->toBase();
    }
    $workIds = $rankingList->map(function ($rank, $_) {
                              return $rank->work_id;
                            })->toArray();
    $works = $this->workRepo
                  ->findAllByIds($workIds, false, ['user_id', 'listen_count']);

    return $rankingList->map(function ($rank, $_) use ($works) {
      $work = $works->where('_id', $rank->work_id)->first();
      if ( ! $work) {
        return null;
      }

      return (object) [
        'workId'      => $rank->work_id,
        'listenCount' => (int) $work->listen_count + $this->getWorkListenNum($work->id),
        'userId'      => $work->user_id,
      ];
    })->filter(function ($work, $_) {
      return ! is_null($work);
    })->toBase();
  }

  /**
   * {@inheritdoc}
   */
  public function getMusicChorusRankingList(string $musicId) : Collection
  {
    $rankingList = $this->workRankingListRepo
                        ->findAllMusicIdAndType($musicId, MusicWorkRankingList::TYPE_CHORUS);
    
    // 如果排行榜为空，表示尚未生成，使用默认排行
    if ($rankingList->isEmpty()) {
      return $this->workRepo
                  ->findAllChorusByMusicId($musicId)
                  ->map(function ($work, $_) {
                    return (object) [
                      'workId'    => $work->id,
                      'userId'    => $work->user_id,
                      'chorusCount' => (int) array_get($work->chorus_start_info, 'chorus_count'),
                    ];
                  });
    }

    $workIds = $rankingList->map(function ($rank, $_) {
                              return $rank->work_id;
                            })->toArray();
    $works = $this->workRepo
                  ->findAllByIds($workIds, false, ['user_id', 'chorus_start_info.chorus_count']);

    return $rankingList->map(function ($rank, $_) use ($works) {
      $work = $works->where('_id', $rank->work_id)->first();
      if ( ! $work) {
        return null;
      }

      return (object) [
        'workId'      => $rank->work_id,
        'chorusCount' => (int) array_get($work->chorus_start_info, 'chorus_count'),
        'userId'      => $work->user_id,
      ];
    })->filter(function ($work, $_) {
      return ! is_null($work);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getChorusStartAccompaniment(string $workId) : \stdClass
  {
    $fields = [
      'user_id', 'chorus_type',
      'chorus_start_info.resource.zip',
      'status'
    ];
    $work = $this->workRepo->findOneById($workId, $fields);
    if (
      ! $work ||
      ! $work->isNormal() ||
      object_get($work, 'chorus_type') != WorkConstant::CHORUS_TYPE_START
    ) {
      throw new WorkNotExistsException('chorus work not exists');
    }

    if ( ! ($resourceZip = array_get($work->chorus_start_info, 'resource.zip'))) {
      throw new WorkChorusAccompanimentPrepareException();
    }

    return (object) [
      'userId'    => $work->user_id,
      'resource'  => $resourceZip,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function incrWorkChorusCount(string $workId) : bool
  {
    return $this->workRepo->incrWorkChorusCount($workId);
  }

    /**
     * {@inheritdoc}
     */
    public function decrWorkChorusCount(string $workId) : bool
    {
        return $this->workRepo->decrWorkChorusCount($workId);
    }

  /**
   * {@inheritdoc}
   */
  public function hasMusicOwnChorusStartWork(string $musicId) : bool
  {
    return $this->workRepo->findOneChorusStartByMusicId($musicId) ? true : false;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendWorkSheet(string $sheetId) : ?\stdClass
  {
    $sheet = $this->recommendWorkSheetRepo->findOneById($sheetId);
    if ( ! $sheet) {
      return null;
    }
    $works = $this->getWorksByIds($sheet->works_ids)
                  ->map(function ($work, $_) {
                    return $work->isNormal ? (object) [
                      'workId'          => $work->workId,
                      'musicId'         => $work->musicId,
                      'userId'          => $work->userId,
                      'workName'        => $work->workName,
                      'cover'           => $work->cover,
                      'description'     => $work->description,
                      'listenCount'     => $work->listenCount,
                      'favouriteCount'  => $work->favouriteCount,
                      'commentCount'    => $work->commentCount,
                      'transmitCount'   => $work->transmitCount,
                      'isPrivate'       => $work->isPrivate,
                      'chorusType'      => $work->chorusType,
                      'chorusCount'     => $work->chorusCount,
                      'originWorkId'    => $work->originWorkId,
                      'resource'        => $work->resource,
                      'createdAt'       => $work->createdAt,
                      'workPopularity'  => $work->workPopularity,
                      'giftAmount' => $work->giftAmount,
                      'giftCoinAmount' => $work->giftCoinAmount,
                      'giftPopularity' => $work->giftPopularity,
                    ] : null;
                  })->filter(function ($work, $_) {
                    return ! is_null($work);
                  });
    return (object) [
      'title'         => $sheet->title,
      'cover'         => is_array($sheet->cover) ? array_get($sheet->cover, 0) : $sheet->cover,
      'recommendText' => $sheet->comments,
      'works'         => $works,
    ];
  }

  private function addWorkListenNum(string $workId)
  {
    $key = $this->workListenNumKey($workId);
    Cache::increment($key);
      //  更新作品和用户的人气值
    $job = (new UpdateWorkPopularity($workId))->onQueue('sing_plus_hierarchy_update');
    dispatch($job);
  }

  private function getWorkListenNum(string $workId) : int
  {
    $key = $this->workListenNumKey($workId);
    return (int) Cache::get($key);
  }

  private function addUserWorkListenNum(string $userId)
  {
    $key = $this->userWorkListenNumKey($userId);
    Cache::increment($key);
  }

  private function workListenNumKey(string $workId)
  {
    return sprintf('work:%s:listennum', $workId);
  }

  private function userWorkListenNumKey(string $userId)
  {
    return sprintf('user:%s:listennum', $userId);
  }

  /**
     * 兼容客户端在3.0.2 之前 的版本无法适配新的站内转发类型
     * @param string $version
     * @param int $type
     * @return int
     */
  public function compatCommenType(?string $version, int $type): int
  {
     if ($version){
         if (version_compare($version, '3.0.2', '<') && $type == Comment::TYPE_TRANSIMIT_INNER){
                return Comment::TYPE_TRANSIMIT;
         }
     }
     return $type;
  }

    /**
     * @param string $workId
     * @return null|\stdClass
     *              - workId
     *              - userId
     *              - giftAmount
     *              - giftCoinAmount
     *              - giftPopularity
     *              - workPopulariy
     */
    public function getWorkById(string $workId): ?\stdClass
    {
        $work = $this->workRepo->findOneById($workId);
        if ($work){
            $giftInfo = $work->gift_info;
            return (object)[
               'workId' => $work->id,
               'userId' => $work->user_id,
               'workPopulariy' => $work->popularity ? $work->popularity : 0,
               'giftAmount' => $giftInfo ? array_get($giftInfo, 'gift_amount', 0): 0,
               'giftCoinAmount' => $giftInfo ? array_get($giftInfo, 'gift_coin_amount', 0) : 0,
               'giftPopularity' => $giftInfo ? array_get($giftInfo, 'gift_popularity_amount', 0) : 0,
            ];
        }else {
            return null;
        }
    }

    /**
     * @param string $userId
     * @param string $workId
     * @param bool|null|null $isPrivate
     * @param null|string|null $cover
     * @param null|string|null $desc
     * @return mixed
     */
    public function updateWorkInfo(
        string $userId,
        string $workId,
        ?bool $isPrivate = null,
        ?string $cover = null,
        ?string $desc = null
    ){
        $work = $this->workRepo->findOneById($workId);
        if (!$work){
            throw new WorkNotExistsException();
        }

        if ($userId != $work->user_id){
            throw new ModifyActionForbiddenException();
        }

        if (!is_null($isPrivate)) {
            if ($work->chorus_type &&
                ($work->chorus_type == WorkConstant::CHORUS_TYPE_START||$work->chorus_type == WorkConstant::CHORUS_TYPE_JOIN)){
                throw new WorkSetPrivateActionForBiddenException();
            }else {
                $work->is_private = $isPrivate ? 1 : 0;
            }
        }
        if ( ! is_null($cover)) {
            $work->cover = $cover;
        }
        if ( ! is_null($desc)) {
            $work->description = $desc;
        }

        $work->save();
    }

    /**
     * @param string $workTag
     * @param null|string $id
     * @param bool $isNext
     * @param int $size
     * @return Collection       elements are \stdClass, properties as below:
     *                          - id string             for pagination
     *                          - workId string         work id
     *                          - userId string         user id
     *                          - musicId string        music id
     *                          - workName ?string
     *                          - cover string          user image uri
     *                          - description string    work description, default for share text
     *                          - listenCount int       work listen count by others
     *                          - commentCount int      work comment count by others (not include replies)
     *                          - favouriteCount int    work favourite count by others
     *                          - transmitCount int     work be transmit count by others from sing+
     *                          - resource string       work resource uri
     *                          - createdAt string      datetime, format: Y-m-d H:i:s
     *                          - chorusType ?int       work chorus type
     *                          - chorusCount int
     *                          - originWorkId ?string  origin work id
     */
    public function getTagWorksList(string $workTag,
                                    ?string $id,
                                    bool $isNext,
                                    int $size): Collection
    {
        $displayOrder = null;
        if ($id) {
            $work = $this->workRepo->findOneById($id, ['display_order']);
            $displayOrder = $work ? $work->display_order : null;
        }
        return $this->workRepo
            ->findAllByWorkTagForPagination($workTag, $displayOrder, $isNext, $size)
            ->map(function ($work, $_) {
                return (object) [
                    'id'              => $work->id,
                    'workId'          => $work->id,
                    'userId'          => $work->user_id,
                    'musicId'         => $work->music_id,
                    'workName'        => isset($work->name) ? $work->name : null,
                    'cover'           => $work->cover,
                    'description'     => $work->description,
                    'listenCount'     => (int) $work->listen_count + $this->getWorkListenNum($work->id),
                    'commentCount'    => (int) $work->comment_count,
                    'favouriteCount'  => (int) $work->favourite_count,
                    'transmitCount'   => (int) $work->transmit_count,
                    'resource'        => $work->resource,
                    'createdAt'       => $work->created_at,
                    'chorusType'      => object_get($work, 'chorus_type'),
                    'chorusCount'     => (object_get($work, 'chorus_type') == WorkConstant::CHORUS_TYPE_START) && object_get($work, 'chorus_start_info') ?
                        (int) array_get($work->chorus_start_info, 'chorus_count', 0) : 0,
                    'originWorkId'  => object_get($work, 'chorus_join_info') ?
                        array_get($work->chorus_join_info, 'origin_work_id') : null,
                ];
            });
    }

    /**
     * @param string $workTag
     * @param null|string $id
     * @param bool $isNext
     * @param int $size
     * @return Collection
     */
    public function getTagWorkSelection(string $workTag,
                                        ?string $id,
                                        bool $isNext,
                                        int $size): Collection{
        $displayOrder = null;
        if ($id) {
            $work = $this->tagWorkSelectionRepo->findOneByWorkIdAndTag($id, $workTag, ['display_order']);
            $displayOrder = $work ? $work->display_order : null;
        }

        return $this->tagWorkSelectionRepo->findAllByWorkTagForPagination($workTag, $displayOrder, $isNext, $size);
    }
}
