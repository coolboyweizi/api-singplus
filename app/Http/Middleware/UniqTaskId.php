<?php

namespace SingPlus\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use SingPlus\Contracts\Admins\Services\AdminTaskService as AdminTaskServiceContract;
use SingPlus\Exceptions\Admins\AdminTaskIdExistsException;
use SingPlus\Exceptions\Admins\AdminTaskIdMissedException;

class UniqTaskId
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @param  string|null  $guard
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    if ( ! $this->enable()) {
      return $next($request);
    }

    $taskId = $request->input('taskId') ?: $request->json('taskId');
    $this->checkTask($taskId);
    $response = $next($request);
    $this->saveTaskId($taskId, $request);
    return $response;
  }

  private function checkTask(?string $taskId)
  {
    $service = app()->make(AdminTaskServiceContract::class);
    if ( ! $taskId) {
      throw new AdminTaskIdMissedException();
    }
    if ($service->isTaskIdExists($taskId)) {
      throw new AdminTaskIdExistsException();
    }
  }

  private function saveTaskId(string $taskId, Request $request)
  {
    $service = app()->make(AdminTaskServiceContract::class);
    $service->saveTaskId($taskId, [
                    'url'     => $request->url(),
                    'body'    => $request->getContent(),
                ]);
  }

  private function enable()
  {
    return ! app()->bound('middleware.request.taskid.disable') ||
           app()->make('middleware.request.taskid.disable') === false;
  }
}
