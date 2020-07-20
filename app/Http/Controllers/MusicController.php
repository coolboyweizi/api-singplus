<?php

namespace SingPlus\Http\Controllers;

use Auth;
use Validator;
use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\MusicService;
use SingPlus\Contracts\Searchs\Services\SearchService as SearchServiceContract;
use SingPlus\Exceptions\Musics\MusicRecommendSheetNotExistsException;

class MusicController extends Controller
{
  /**
   * Get user recommand music
   */
  public function getRecommends(
    Request $request,
    MusicService $musicService
  ) {
    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $musics = $musicService->getRecommends($actingUserId, $countryAbbr);

    return $this->render('musics.recommends', [
      'musics'  => $musics,
    ]);
  }

  /**
   * Get hot music
   */
  public function getHots(
    Request $request,
    MusicService $musicService
  ) {
    $this->validate($request, [
      'id'      => 'uuid',
      'isNext'  => 'boolean',
      'size'    => 'integer|min:1|max:50',
    ]);
    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";

    $musics = $musicService->getHots(
      $actingUserId,
      $request->query->get('id'),  
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', 15),
      $countryAbbr
    );

    return $this->render('musics.hots', [
      'musics'  => $musics,
    ]);
  }

  /**
   * Get recommend music sheet
   */
  public function getRecommendMusicSheet(
    Request $request,
    MusicService $musicService,
    string $sheetId
  ) {
    // check taskId
    $validator = Validator::make([
      'sheetId'  => $sheetId,
    ], [
      'sheetId'  => 'required|uuid', 
    ]);

    if ($validator->fails()) {
      $sheet = null;
    } else {
      $actingUser = $request->user();
      $actingUserId = $actingUser ? $actingUser->id : "";
      $actingUserId = Auth::check() ? $actingUserId : "";
      $sheet = $musicService->getRecommendMusicSheet(
          $actingUserId, $sheetId
      );
    }

    return $this->render('musics.recommendMusicSheet', [
      'sheet' => $sheet,
    ]);
  }

  /**
   * Get music category
   */
  public function getCategories(
    Request $request,
    MusicService $musicService
  ) {
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $categories = $musicService->getCategories(
        $actingUserId
    );

    return $this->render('musics.categories', [
      'languages' => $categories->languages,
      'styles'    => $categories->styles,
    ]);
  }

    /**
     * Get search suggest
     */
    public function searchSuggest(
        Request $request,
        SearchServiceContract $searchService
    ) {
        $this->validate($request, [
            'search'    => 'required|string|max:50',
        ]);

        $suggests = $searchService->musicSearchSuggest($request->query->get('search'));

        return $this->renderInfo('success', [
            'suggests'   => $suggests,
        ]);
    }

  /**
   * List musics
   */
  public function listMusics(
    Request $request,
    MusicService $musicService
  ) {
    $this->validate($request, [
      'artistId'    => 'uuid',
      'languageId'  => 'uuid',
      'styleId'     => 'uuid',
      'search'      => 'string|max:128|required_without_all:artistId,languageId,styleId',
      'musicId'     => 'uuid',
      'isNext'      => 'boolean',
      'size'        => 'integer|min:1|max:50',
      'page'        => 'integer|min:1|max:100',
    ]);
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";

    // process page
    $page = 0;
    if ( ! $request->query->get('page')) {
        if ($request->query->get('search') && $musicId = $request->query->get('musicId')) {
            $searchPages = $request->session()->get('searchPages', []);
            $musicPage = array_get($searchPages, $musicId);
            if ( ! is_null($musicPage)) {
                $page = $musicPage;
            } else {
                $allPags = array_values($searchPages);
                $maxPages = $allPags ? max($allPags) : 0;
                $searchPages[$musicId] = $maxPages + 1;
                $request->session()->put('searchPages', $searchPages);
                $page = $searchPages[$musicId];
            }
        } else {
            $request->session()->put('searchPages', []);
        }
    } else {
        $page = $request->query->get('page') - 1;
    }
    
    $musics = $musicService->getMusics(
    $actingUserId,
      [
        'artistId'    => $request->query->get('artistId'),
        'languageId'  => $request->query->get('languageId'),
        'styleId'     => $request->query->get('styleId'),
        'search'      => $request->query->get('search'),
      ],
      $request->query->get('musicId'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize),
      $page
    );

    return $this->render('musics.list', [
      'musics'  => $musics,
    ]);
  }

  /**
   * Get musics list by style
   */
  public function listMusicsByStyle(
    Request $request,
    MusicService $musicService
  ) {
    $this->validate($request, [
      'styleId'     => 'uuid',
      'musicId'     => 'uuid',
      'isNext'      => 'boolean',
      'size'        => 'integer|min:1|max:50',
    ]);
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $musics = $musicService->getStyleMusics(
      $actingUserId,
      $request->query->get('styleId'),
      $request->query->get('musicId'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('musics.list', [
      'musics'  => $musics,
    ]);
  }

  /**
   * Tips for sing
   */
  public function getTips()
  {
    return $this->render('musics.tips');
  }

  /**
   * Get music download address
   */
  public function getMusicDownloadAddress(
    Request $request,
    MusicService $musicService
  ) {
    $this->validate($request, [
      'musicId' => 'required|uuid',
    ]);

    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $music = $musicService->getMusicDownloadAddress(
      $actingUserId,
      $request->query->get('musicId'),
      $countryAbbr
    );

    return $this->render('musics.download', [
      'downloadUrl' => $music->resource,
      'cover'       => $music->cover,
    ]);
  }

  /**
   * Get music detail
   */
  public function getMusicDetail(
    Request $request,
    MusicService $musicService,
    $musicId
  ) {
    $request->query->set('musicId', $musicId);
    $this->validate($request, [
      'musicId' => 'required|uuid',
      'basic'   => 'integer|in:0,1',
    ]);

    $basic = (bool) $request->query->get('basic', 0);
    $music = $musicService->getMusicDetail($musicId, $basic);

    return $this->render('musics.detail', [
      'music' => $music,
      'basic' => $basic,
    ]);
  }
}
