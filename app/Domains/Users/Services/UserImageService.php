<?php

namespace SingPlus\Domains\Users\Services;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\SeqCounter;
use SingPlus\Contracts\Users\Services\UserImageService as UserImageServiceContract;
use SingPlus\Domains\Users\Repositories\UserProfileRepository;
use SingPlus\Domains\Users\Repositories\UserImageRepository;
use SingPlus\Domains\Users\Models\UserImage;
use SingPlus\Exceptions\Users\UserImageNotExistsException;
use SingPlus\Exceptions\Users\UserImageOperateForbiddenException;

class UserImageService implements UserImageServiceContract
{
  /**
   * @var UserProfileRepository
   */
  private $userProfileRepo;

  /**
   * @var UserImageRepository
   */
  private $userImageRepo;

  public function __construct(
    UserProfileRepository $userProfileRepo,
    UserImageRepository $userImageRepo
  ) {
    $this->userProfileRepo = $userProfileRepo;
    $this->userImageRepo = $userImageRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function addUserImage(string $userId, string $uri) : ?string
  {
    $image = new UserImage([
          'user_id'       => $userId,
          'uri'           => $uri,
          'is_avatar'     => UserImage::AVATAR_NO,
          'display_order' => SeqCounter::getNext('user_images'),
    ]);
    return $image->save() ? $image->id : null;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUserImages(string $userId, array $imageIds) : Collection
  {
    $images = $this->userImageRepo->findAllByUserIdAndIds($userId, $imageIds);
    if ($images->count() != count($imageIds)) {
      throw new UserImageNotExistsException(
        'there are some images not exists or not belongs to you');
    }
    if ($images->contains(function ($image, $_) {
      return $image->is_avatar == UserImage::AVATAR_YES;
    })) {
      throw new UserImageOperateForbiddenException('avatar can\'t delete');
    }

    $success = $this->userImageRepo->deleteAllByIds($imageIds);

    if ($success) {
      return $images->map(function ($image, $_) {
        return $image->uri;
      });
    } else {
      return collect();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGallery(string $userId, ?string $imageId, bool $isNext, int $size) : Collection 
  {
    $displayOrder = null;
    if ($imageId) {
      $image = $this->userImageRepo->findOneById($imageId, ['display_order']);
      $displayOrder = $image ? $image->display_order : null;
    }

    $images = $this->userImageRepo
                ->findAllByUserIdForPagination($userId, $displayOrder, $isNext, $size);

    // we should make sure avatar always at the first place
    if ( ! $displayOrder) {
      $avatar = $this->userImageRepo->findOneAvatar($userId);
      if ($avatar) {
        $images->prepend($avatar);
      }
    }

    return $images->map(function ($image, $_) {
              return (object) [
                'imageId'   => $image->id,
                'uri'       => $image->uri,
                'isAvatar'  => (bool) $image->is_avatar,
              ];
            });
  }

  /**
   * {@inheritdoc}
   */
  public function getAvatar(string $userId) : ?string
  {
    $avatar = $this->userImageRepo->findOneAvatar($userId);

    return $avatar ? $avatar->uri : null;
  }

  /**
   * {@inheritdoc}
   */
  public function setAvatar(string $userId, string $imageId)
  {
    $image = $this->userImageRepo->findOneById($imageId);
    if (empty($image) || $image->user_id != $userId) {
      throw new UserImageNotExistsException();
    }

    if ($image->is_avatar == UserImage::AVATAR_YES) {
      return true;
    }

    $currentAvatar = $this->userImageRepo->findOneAvatar($userId);
    if ($currentAvatar) {
      $currentAvatar->is_avatar = UserImage::AVATAR_NO;
      $currentAvatar->save();
    }

    $image->is_avatar = UserImage::AVATAR_YES;
    $image->save();

    // update user profile
    $profile = $this->userProfileRepo->findOneByUserId($userId);
    if (empty($profile)) {
      $profile = new UserProfile([
        'user_id' => $userId,
      ]);
    }
    $profile->avatar = $image->uri;
    $profile->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getImages(array $imageIds) : Collection
  {
    return $this->userImageRepo
             ->findAllByIds($imageIds)
             ->map(function ($image, $_) {
                return (object) [
                  'imageId'   => $image->id,
                  'uri'       => $image->uri,
                  'isAvatar'  => $image->is_avatar == UserImage::AVATAR_YES ? true : false,
                ];
             });
  }

  /**
   * {@inheritdoc}
   */
  public function countUserGalleryImage(string $userId) : int
  {
    return $this->userImageRepo->countImagesByUserId($userId);
  }
}
