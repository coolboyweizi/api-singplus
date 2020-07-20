<?php
namespace SingPlus\Domains\News\Services;
use Illuminate\Support\Collection;
use SingPlus\Contracts\News\Services\NewsServices as NewsServiceContract;
use SingPlus\Domains\News\Models\News;
use SingPlus\Domains\News\Repositories\NewsRepository;
use SingPlus\Domains\Works\Repositories\WorkRepository;
use SingPlus\Exceptions\Works\WorkNotExistsException;
use SingPlus\Support\Database\SeqCounter;

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/18
 * Time: 下午5:32
 */
class NewsServices implements NewsServiceContract
{
    /**
     * @var NewsRepository
     */
    private $newsRepo;

    /**
     * @var WorkRepository
     */
    private $workRepo;

    public function __construct(NewsRepository $newsRepository, WorkRepository $workRepository)
    {
        $this->newsRepo = $newsRepository;
        $this->workRepo = $workRepository;
    }

    /**
     * @param string $newsId
     * @return mixed
     */
    public function deleteNews(string $newsId)
    {
        $news = $this->newsRepo->findOneById($newsId, ['status']);
        if ($news){
            $news->status = News::STATUS_DELETED;
            $news->save();
        }
    }

    /**
     * @param string $userId
     * @param string $type
     * @param string $workdId
     * @param null|string $desc
     * @return \stdClass
     *              - news_id       string the id of the news been created
     */
    public function createNews(string $userId, string $type, string $workdId, ?string $desc): \stdClass
    {
        $work = $this->workRepo->findOneById($workdId, ['user_id', 'comment_count']);
        if ( ! $work) {
            throw new WorkNotExistsException();
        }
        $news = new News([
            'user_id' => $userId,
            'type'    => $type,
            'detail' => [
                'work_id' => $workdId
            ],
            'status' => News::STATUS_NORMAL,
            'desc'  => $desc,
            'display_order' =>SeqCounter::getNext('news'),
        ]);
        $news->save();
        return (object)['news_id' => $news->id];
    }

    /**
     * @param array $userIds
     * @param null|string $id
     * @param bool $isNext
     * @param int $size
     * @return Collection
     *                  - id      string the id of news
     *                  - newsId    string      the news's Id
     *                  - userId    string      the id of a user
     *                  - type      string      the type of news
     *                  - workId    string      the id of relation work
     *                  - desc      string      the desc of news
     *                  - createdAt  Carbon     the date
     */
    public function getUsersNews(
        array $userIds,
        ?string $id,
        bool $isNext,
        int $size
    ): Collection
    {
        $displayOrder = null;
        if ($id) {
            $news = $this->newsRepo->findOneById($id, ['display_order']);
            $displayOrder = $news ? $news->display_order : null;
        }
        return $this->newsRepo
            ->findAllByUserIdsForPagination($userIds, $displayOrder, $isNext, $size)
            ->map(function ($news, $_) {
                $detail = $news ? $news->detail : null;
                return (object) [
                    'id'              => $news->id,
                    'newsId'          => $news->id,
                    'userId'          => $news->user_id,
                    'type'            => $news->type,
                    'workId'         => $detail ? array_get($detail, 'work_id') : null,
                    'desc'        => $news->desc,
                    'createdAt'   => $news->created_at
                ];
            });
    }


    /**
     * {@inheritdoc} 
     */
    public function getNews(array $newsIds) : Collection
    {
        return $this->newsRepo
                    ->findAllByIds($newsIds)
                    ->map(function ($news, $_) {
                        $detail = $news ? $news->detail : null;
                        return (object) [
                            'id'              => $news->id,
                            'newsId'          => $news->id,
                            'userId'          => $news->user_id,
                            'type'            => $news->type,
                            'workId'         => $detail ? array_get($detail, 'work_id') : null,
                            'desc'        => $news->desc,
                            'createdAt'   => $news->created_at
                        ];
                    });
    }

    /**
     * Get news by created time desc
     *
     * @param string $newsId news id
     * @param bool $force deleted news will be return if true
     *
     * @return \stdClass        properties as below:
     *                          - newsId string         news id
     *                          - userId string         user id
     *                          - workId string        work id
     *                          - desc ?string
     *                          - createdAt string      datetime, format: Y-m-d H:i:s
     *                          - isNormal bool         specify whether work is normal or not
     */
    public function getDetail(string $newsId, bool $force = false): ?\stdClass
    {

        $news = $this->newsRepo->findOneById($newsId);

        $detail = $news ? $news->detail : null;

        return (( ! $force && $news && $news->isNormal()) || ($force && $news)) ? (object) [
            'newsId'          => $news->id,
            'userId'          => $news->user_id,
            'workId'          =>  $detail ? array_get($detail, 'work_id') : null,
            'desc'        => $news->desc,
            'createdAt'       => $news->created_at,
            'isNormal'        => $news->isNormal(),
        ] : null;
    }
}
