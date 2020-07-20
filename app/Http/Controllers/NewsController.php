<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/18
 * Time: 下午4:53
 */

namespace SingPlus\Http\Controllers;


use Illuminate\Http\Request;
use SingPlus\Services\NewsService;

class NewsController extends Controller
{


    /**
     * delete a news for user
     */
    public function deleteNews(
        Request $request,
        NewsService $newsService)
    {
        $this->validate($request, [
            'newsId' => 'required|uuid',
        ]);
        $newsService->deleteNews(
            $request->user()->id,
            $request->request->get('newsId')
        );

        return $this->renderInfo('success');
    }

    /**
     * get user related news list
     */
    public function getNewsList(Request $request,
                                NewsService $newsService)
    {
        $this->validate($request, [
            'newsId'  => 'uuid|required_with:isNext',
            'isNext'  => 'boolean',
            'size'    => 'integer|min:1|max:50',
            'self'  => 'required|boolean',
            'userId' => 'uuid',
        ]);

        $actionUser = $request->user();
        $actionUserId = $actionUser ? $actionUser->id : "";
        $news = $newsService->getNewsLists(
            $actionUserId,
            (bool)$request->query->get('self'),
            $request->query->get('newsId'),
            (bool) $request->query->get('isNext', true),
            (int) $request->query->get('size', $this->defaultPageSize),
            $request->query->get('userId')
        );
        return $this->render('news.latests', [
            'news' => $news,
        ]);
    }

    /**
     * Get user list version 4
     */
    public function getNewsList_v4(
        Request $request,
        NewsService $newsService
    ) {
        $this->validate($request, [
            'self'      => 'required|boolean',
            'userId'    => 'uuid',
            'page'      => 'integer|min:1',
            'size'      => 'integer|min:1|max:50',
        ]);

        $news = $newsService->getNewsLists_v4(
            $request->user() ? $request->user()->id : null,
            (bool) $request->query->get('self'),
            $request->query->get('userId'),
            (int) $request->query->get('page', 1),
            (int) $request->query->get('size', $this->defaultPageSize)
        );

        return $this->render('news.latests', [
            'news'  => $news,
        ]);
    }

    /**
     *  create a news
     */
    public function createNews(Request $request, NewsService $newsService)
    {
        $this->validate($request,[
           'workId' => 'required|uuid',
            'desc' => 'string',
            'type' => 'required|string',
        ]);
        $result = $newsService->createNews($request->user()->id,
            $request->request->get('type'),
            $request->request->get('desc'),
            $request->request->get('workId'));
        return $this->renderInfo('success');
    }

}
