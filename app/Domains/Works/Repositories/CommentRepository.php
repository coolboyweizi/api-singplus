<?php

namespace SingPlus\Domains\Works\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Works\Models\Comment;

class CommentRepository
{
  /**
   * @param string $id      work id
   * @param array $fields   specify which fields should be return
   */
  public function findOneById(string $id, array $fields = ['*']) : ?Comment
  {
    return Comment::select(...$fields)->find($id);
  }

  /**
   * @param string $workId      work id
   * @param ?int $displayOrder  used for pagination
   * @param bool $isNext        used for pagination
   * @param int $size           used for pagination
   *
   * @return Collection         elements are Comment
   */
  public function findWorkAllForPagination(
    string $workId,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    $query = Comment::where('work_id', $workId)
                    ->where('status', Comment::STATUS_NORMAL);
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }

  /**
   * @param string $repliedUserId     replied user id
   * @param ?int $displayOrder        used for pagination
   * @param bool $isNext              used for pagination
   * @param int $size                 used for pagination
   *
   * @return Collection               elements are Comment
   */
  public function findAllUserRelatedForPagination(
    string $repliedUserId,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    $query = Comment::with([
                        'work' => function ($query) {
                                    $query->select('music_id'); 
                                  },
                        'repliedComment' => function ($query) {
                                    $query->select('content')
                                          ->where('status', Comment::STATUS_NORMAL);
                                  },
                    ])
                    ->where('replied_user_id', $repliedUserId)
                    ->where('author_id', '<>', $repliedUserId)
                    ->where('status', Comment::STATUS_NORMAL);
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }

  /**
   * Fetch all comments by ids
   *
   * @param array $commentIds     elements are comment id
   * @param bool $force           deleted comment will be fetch if $force is true
   *
   * @return Collection           elements are Comment
   */
  public function findAllByIds(array $commentIds, $force = false) : Collection
  {
    $validStatus = [Comment::STATUS_NORMAL];
    if ($force) {
      $validStatus = [Comment::STATUS_NORMAL, Comment::STATUS_DELETED];
    }
    $query =  Comment::with([
                        'work'  => function ($query) {
                                      $query->select('music_id', 'name');
                                    },
                        'repliedComment'  => function ($query) use ($force, $validStatus) {
                                      $query->select('content')
                                            ->whereIn('status', $validStatus);
                                    },
                     ])
                     ->whereIn('_id', $commentIds);
    return $query->whereIn('status', $validStatus)
                 ->get();
  }
}
