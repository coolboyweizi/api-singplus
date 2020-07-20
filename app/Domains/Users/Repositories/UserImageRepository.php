<?php

namespace SingPlus\Domains\Users\Repositories;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Users\Models\UserImage;

class UserImageRepository
{
  /**
   * @param string $userId
   * @param array $imageIds     elements are image id
   *
   * @return Collection         elements are UserImage
   */
  public function findAllByUserIdAndIds(string $userId, array $imageIds) : Collection
  {
    return UserImage::where('user_id', $userId)
                    ->whereIn('_id', $imageIds)
                    ->get();
  }

  /**
   * @param string $userId
   * @param ?int $displayOrder    used for pagination
   * @param bool $isNext          used for pagination
   * @param int $size             used for pagination
   *
   * @return Collection           elements are UserImage
   */
  public function findAllByUserIdForPagination(
    string $userId,
    ?int $displayOrder,
    bool $isNext,
    int $size,
    $needAvatar = false
  ) : Collection {
    $query = UserImage::where('user_id', $userId);
    if ( ! $needAvatar) {
      $query->where('is_avatar', UserImage::AVATAR_NO);
    }
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }

    return $query->get();
  }

  /**
   * @param string $userId
   */
  public function findOneAvatar(string $userId) : ?UserImage
  {
    return UserImage::where('user_id', $userId)
                         ->where('is_avatar', UserImage::AVATAR_YES)
                         ->first();
  }

  /**
   * @param array $userIds
   *
   * @return Collection     elements are UserImage
   */
  public function findAllAvatars(array $userIds) : Collection
  {
    if (empty($userIds)) {
      return collect();
    }
    return UserImage::whereIn('user_id', $userIds)
                    ->where('is_avatar', UserImage::AVATAR_YES)
                    ->get();
  }

  /**
   * @param array $imageIds     elements are image id
   *
   * @param int   indicate how many records be deleted
   */
  public function deleteAllByIds(array $imageIds) : int
  {
    return UserImage::whereIn('_id', $imageIds)->delete();
  }

  /**
   * Find all images by ids
   *
   * @param array $imageIds
   *
   * @return Collection         elements are UserImage
   */
  public function findAllByIds(array $imageIds) : Collection
  {
    if (empty($imageIds)) {
      return collect();
    }
    return UserImage::whereIn('_id', $imageIds)->get();
  }

  /**
   * @param string $imageId
   *
   * @return ?UserImage
   */
  public function findOneById(string $imageId, array $fields = ['*']) : ?UserImage
  {
    return UserImage::select(...$fields)->find($imageId);
  }

  /**
   * @param string $userId
   *
   * @return int
   */
  public function countImagesByUserId(string $userId) : int
  {
    return UserImage::where('user_id', $userId)->count();
  }
}
