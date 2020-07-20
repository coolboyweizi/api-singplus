<?php

namespace SingPlus\Domains\Searchs\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Searchs\Services\SearchService as SearchServiceContract;

class SearchService implements SearchServiceContract
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
     * {@inheritdoc}
     */
    public function musicSearch(string $searchWord, int $page = 0, int $size = 50) : Collection
    {
        $url = rtrim($this->domain, '/') . '/musics/?' . http_build_query([
            'search'    => $searchWord,
            'page'      => $page,
            'size'      => $size,
        ], null, '&', PHP_QUERY_RFC3986);

        $result = $this->get($url);
        $data = collect(object_get($result, 'data.data'));

        return $data->map(function ($item, $_) {
            $calArtists = [];
            if (($artists = object_get($item, 'artists')) && is_array($artists)) {
                $calArtists[] = (object) [
                    'artistId'      => $artists[0],
                    'name'  => object_get($item, 'artists_name'),
                ];
            }
            return (object) [
                'musicId'   => object_get($item, '_id'),
                'name'      => object_get($item, 'name'),
                'size'      => (object) [
                    'raw'           => object_get($item, 'resource.raw.size', 0),
                    'accompaniment' => object_get($item, 'resource.accompaniment.size', 0),
                    'total'         => object_get($item, 'resource.size', 0),
                ],
                'artists'   => $calArtists,
                'highlight' => object_get($item, 'highlight', []),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function musicSearchSuggest(string $searchWord) : Collection
    {
        $url = rtrim($this->domain, '/') . '/musics/suggest?' . http_build_query([
            'search'    => $searchWord,
        ], null, '&', PHP_QUERY_RFC3986);

        $result = $this->get($url);
        $suggests = collect(object_get($result, 'data.suggests', []));

        return $suggests->map(function ($item, $_) {
            return (object) [
                'search'            => $item->search,
                'suggest_raw'       => $item->suggest_raw,
                'suggest_display'   => $item->suggest_display,
                'source'            => $item->source,
            ];
        });
    }

    /**
     * Send a get request to search service
     *
     * @param string $url
     *
     * @return \stdClass
     */
    protected function get(string $url) : \stdClass
    {
        return $this->rawRequestAndParseResponse('GET', $url);
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

        return $result;
    }
}
