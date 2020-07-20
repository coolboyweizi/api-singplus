<?php

namespace SingPlus\Domains\Friends\Services;

use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;


class GraphFriendService
{
    const RESP_CODE_SUCCESS = 0;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    public function __construct(
        ClientInterface $httpClient
    ) {
        $this->httpClient = $httpClient;
        $this->domain = config('search.domain');
    }

    /**
     * Get user's all followings
     *
     * @param string $userId
     * @param int $page
     * @param int $size
     *
     * @return Collection       items as below:
     *                          - userId string
     *                          - isFollowing bool  always true
     *                          - followAt Carbon
     *                          - isFollower bool
     *                          - followedAt Carbon
     */
    public function getFollowings(string $userId, int $page = 1, int $size = 50) : Collection
    {
        $uri = sprintf('%s/friends/%s/followings', rtrim($this->domain, '/'), $userId);
        $url = $uri . '?' . http_build_query([
            'page'  => $page,
            'size'  => $size,
        ], null, '&', PHP_QUERY_RFC3986);

        $result = $this->get($url);
        $data = collect(object_get($result, 'data.followings'));

        return $data->map(function ($item, $_) {
            return (object) [
                'userId'        => $item->user_id,
                'isFollowing'   => true,
                'followAt'      => Carbon::parse($item->follow_at),
                'isFollower'    => $item->is_follower,
                'followedAt'    => $item->reverse_follow_at ?
                                        Carbon::parse($item->reverse_follow_at) :
                                        null,
            ];
        });
    }


    /**
     * Get user's all followers
     *
     * @param string $userId
     * @param int $page
     * @param int $size
     *
     * @return Collection       item as below:
     *                          - userId string
     *                          - isFollower bool   always true
     *                          - followedAt Carbon
     *                          - isFollowing bool
     *                          - followAt Carbon
     */
    public function getFollowers(string $userId, int $page = 1, int $size = 1) : Collection
    {
        $uri = sprintf('%s/friends/%s/followers', rtrim($this->domain, '/'), $userId);
        $url = $uri . '?' . http_build_query([
            'page'  => $page,
            'size'  => $size,
        ], null, '&', PHP_QUERY_RFC3986);

        $result = $this->get($url);
        $data = collect(object_get($result, 'data.followers'));

        return $data->map(function ($item, $_) {
            return (object) [
                'id'            => $item->user_id,
                'userId'        => $item->user_id,
                'isFollower'    => true,
                'followedAt'    => Carbon::parse($item->follow_at),
                'isFollowing'   => $item->is_following,
                'followAt'      => $item->reverse_follow_at ?
                                        Carbon::parse($item->reverse_follow_at) :
                                        null,
            ];
        });
    }


    /**
     * @param string $userId
     * @param array $targetUserIds
     *
     * @return Collection           items as below:
     *                              - userId string            目标用户userId
     *                              - isFollowing bool    是否是给定用户的following
     *                              - followAt \Carbon\Carbon  给定用户关注目标用户的时间
     *                              - isFollower bool     是否是给定用户的follower
     *                              - followedAt ?\Carbon\Carbon  给定用户被目标用户关注的时间
     *
     */
    public function getUserRelationship(string $userId, array $targetUserIds) : Collection
    {
        # 这里的if是为了解决一个非常操蛋且很贱的问题，在非登录态的情况下，本不应该
        # 进入到这个函数，但是做这个功能的哥们不知道出于什么想法，在非登录情况下也
        # 要获取用户关系。Fuck!!!
        if ( ! $userId) {
            return collect($targetUserIds)->map(function ($userId, $_) {
                return (object) [
                    'userId'        => $userId,
                    'isFollowing'   => false,
                    'followAt'      => null,
                    'isFollower'    => false,
                    'followedAt'    => null,
                ];
            });
        }

        if (empty($targetUserIds)) {
            return collect();
        }

        // 确保json encode后为一个array，而不是dict
        $clearTargetUserIds = [];
        foreach ($targetUserIds as $_ => $v) {
            $clearTargetUserIds[] = $v;
        }

        $url = sprintf('%s/friends/%s/relation', rtrim($this->domain, '/'), $userId);
        $json = [
            'target_user_ids'   => $clearTargetUserIds,
        ];

        $result = $this->get($url, $json);
        $data = collect(object_get($result, 'data.relation'));

        return $data->map(function ($item, $userId) {
            return (object) [
                'userId'        => $userId,
                'isFollowing'   => $item->is_following,
                'followAt'      => $item->follow_at ? Carbon::parse($item->follow_at) : null,
                'isFollower'    => $item->is_follower,
                'followedAt'    => $item->followed_at ? Carbon::parse($item->followed_at) : null,
            ];
        })->values();
    }


    /**
     * @param string $userId
     */
    public function countUserFollowers(string $userId) : int
    {
        $uri = sprintf('%s/friends/%s/followers', rtrim($this->domain, '/'), $userId);
        $url = $uri . '?' . http_build_query([
            'page'  => 1,
            'size'  => 1,
        ], null, '&', PHP_QUERY_RFC3986);

        $result = $this->get($url);
        $followerCount = object_get($result, 'data.follower_count');

        return (int) $followerCount;
    }


    /**
     * Get specified user's followings latest works
     *
     * @param string $userId
     * @param int $page
     * @param int $size
     *
     * @return Collection       elements are work id
     */
    public function getFollowingLatestWorks(string $userId, int $page = 1, int $size = 20) : Collection
    {
        $url = sprintf('%s/friends/%s/following-works?%s',
            rtrim($this->domain, '/'),
            $userId,
            http_build_query([
                'page' => $page,
                'size' => $size,
            ], null, '&', PHP_QUERY_RFC3986));
        $result = $this->get($url);
        return collect(object_get($result, 'data.works'));
    }


    /**
     * Get user following's latest news
     */
    public function getFollowingLatestNews(string $userId, int $page = 1, int $size = 20) : Collection
    {
        $url = sprintf('%s/friends/%s/following-news?%s',
            rtrim($this->domain, '/'),
            $userId,
            http_build_query([
                'page'          => $page,
                'size'          => $size,
                'include_self'  => 'true',
            ], null, '&', PHP_QUERY_RFC3986));
        $result = $this->get($url);
        return collect(object_get($result, 'data.news'));
    }


    /**
     * Get user latest news
     */
    public function getUserLatestNews(string $userId, int $page = 1, int $size = 20) : Collection
    {
        $url = sprintf('%s/friends/%s/news?%s',
            rtrim($this->domain, '/'),
            $userId,
            http_build_query([
                'page'          => $page,
                'size'          => $size,
            ], null, '&', PHP_QUERY_RFC3986));
        $result = $this->get($url);
        return collect(object_get($result, 'data.news'));
    }


    /**
     * Send a get request to search service
     *
     * @param string $url
     *
     * @return \stdClass
     */
    protected function get(string $url, array $data = null) : \stdClass
    {
        $options = [];
        if ($data) {
            $options[RequestOptions::JSON] = $data;
        }
        return $this->rawRequestAndParseResponse('GET', $url, $options);
    }


    /**
     * Send a request
     *
     * @param string $method
     * @param string $url
     * @param array $options
     *
     * @return \stdClass
     */
    protected function rawRequestAndParseResponse(
        string $method,
        string $url,
        array $options = []
    ) : \stdClass {
        $this->httpClient = app()->make(\GuzzleHttp\ClientInterface::class);
        $options = array_merge([
            RequestOptions::VERSION     => '1.1',
            RequestOptions::HEADERS     => [
                'Accept'    => 'application/json',
            ],
            RequestOptions::CONNECT_TIMEOUT => 60,
            RequestOptions::TIMEOUT         => 60,
        ], $options);

        $response = $this->httpClient->request($method, $url, $options);
        if ($response->getStatusCode() != 200) {
            throw new \Exception(sprintf('Request search services: %s error', $url));
        }

        $responseContent = (string) $response->getBody();
        $result = json_decode($responseContent);
        if ( ! $result) {
            throw new \Exception(sprintf('Request search services: %s response format error', $url));
        }

        if ($result->code != self::RESP_CODE_SUCCESS) {
            throw new \Exception(sprintf('Request search services: %s', $result->message));
        }

        return $result;
    }
}
