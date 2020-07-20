<?php

namespace SingPlus\Contracts\Searchs\Services;

use Illuminate\Support\Collection;

interface SearchService
{
    /**
     * @param string $searchWord
     * @param ?int $page
     * @param ?int $size
     *
     * @return Collection       elements are \stdClass, properties as below:
     *                          - musicId string            music id
     *                          - name string               music name
     *                          - size \stdClass
     *                              - raw int               raw size (bytes)
     *                              - accompaniment int     accompaniment size (bytes)
     *                              - total int             zip package size (types)
     *                          - artists array             elements are \stdClass
     *                              - artistId string       artist id
     *                              - name string           artist name
     */
    public function musicSearch(string $searchWord, int $page = 0, int $size = 50) : Collection;

    /**
     * @param string $searchWord
     *
     * @return Collection       elements are \stdClass, properties as below:
     *                          - search string             suggest search word
     *                          - suggest_raw string        suggest from search engine
     *                          - suggest_display string    suggest for display
     *                          - source string             suggest source. valid values:
     *                                                      name_suggest | artists_name_suggest
     */
    public function musicSearchSuggest(string $searchWord) : Collection;
}
