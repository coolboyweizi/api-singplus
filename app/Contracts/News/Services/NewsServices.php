<?php
namespace SingPlus\Contracts\News\Services;
use Illuminate\Support\Collection;

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/18
 * Time: 下午5:30
 */
interface NewsServices
{

    /**
     * Get news by created time desc
     *
     * @param string $newsId   news id
     * @param bool $force           deleted news will be return if true
     *
     * @return \stdClass        properties as below:
     *                          - newsId string         news id
     *                          - userId string         user id
     *                          - workId string        work id
     *                          - desc ?string
     *                          - createdAt string      datetime, format: Y-m-d H:i:s
     *                          - isNormal bool         specify whether work is normal or not
     */
    public function getDetail(string $newsId, bool $force = false) : ?\stdClass;

    /**
     * @param string $newsId
     * @return mixed
     */
    public function deleteNews(string $newsId) ;

    /**
     * @param string $userId
     * @param string $type
     * @param string $workdId
     * @param null|string $desc
     * @return \stdClass
     *              - news_id       string the id of the news been created
     */
    public function createNews(string $userId, string $type, string $workdId, ?string $desc) : \stdClass;

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
    ) : Collection;

    /**
     * @param array $newsIds
     *
     * @return Collection
     *                  - id      string the id of news
     *                  - newsId    string      the news's Id
     *                  - userId    string      the id of a user
     *                  - type      string      the type of news
     *                  - workId    string      the id of relation work
     *                  - desc      string      the desc of news
     *                  - createdAt  Carbon     the date
     */
    public function getNews(array $newsIds) : Collection;
}
