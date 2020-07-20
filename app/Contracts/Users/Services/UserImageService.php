<?php

namespace SingPlus\Contracts\Users\Services;

use Illuminate\Support\Collection;

interface UserImageService
{
  /**
   * Add image to user image gallery
   *
   * @param string $userId      user id
   * @param string $uri         image uri in storage system, which identify a image
   *
   * @return string imageId
   */
  public function addUserImage(string $userId, string $uri) : ?string;

  /**
   * Delete user image
   *
   * avatar can't deleted
   *
   * @param string $userId
   * @param array $imageIds   elements is image id
   *
   * @return Collection       elements are image uri
   */
  public function deleteUserImages(string $userId, array $imageIds) : Collection;

  /**
   * Get user gallery, order by upload time desc
   *
   * @param string $userId
   * @param string $imageId   for pagination
   * @param bool $isNext      for pagination
   * @param int $size         for pagination
   *
   * @return Collection     elements are \stdClass, properties as below
   *                        - imageId string  image id
   *                        - uri string      image uri
   *                        - isAvatar bool   indicate whether this image is user avatar
   */
  public function getGallery(string $userId, ?string $imageId, bool $isNext, int $size) : Collection;

  /**
   * Get user avatar image key
   *
   * @param string $userId
   *
   * @return string           avatar storage key
   */
  public function getAvatar(string $userId) : ?string;

  /**
   * Set user avatar to specified image key
   *
   * @param string $userId
   * @param string $imageId
   */
  public function setAvatar(string $userId, string $imageId);

  /**
   * Get image by image id
   *
   * @param array $imageIds
   *
   * @return Collection         elements are \stdClass, properties as below:
   *                            - imageId string        image id
   *                            - uri string
   *                            - isAvatar bool
   */
  public function getImages(array $imageIds) : Collection;

  /**
   * Count user's gallery images number
   *
   * @param string $userId
   *
   * @return int            gallery images number
   */
  public function countUserGalleryImage(string $userId) : int;
}
