<?php

namespace SingPlus\Services;

use Log;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use GuzzleHttp\Client as GuzzleClient;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Users\Services\UserImageService as UserImageServiceContract;
use SingPlus\Exceptions\Users\UserImageUploadFailedException;
use SingPlus\Exceptions\Users\UserImageNotExistsException;
use SingPlus\Exceptions\Users\UserImageTooManyException;
use SingPlus\Events\UserImageUploaded as EventUserImageUploaded;

class ImageService
{
  /**
   * @var StorageServiceContract
   */
  private $storageService;

  /**
   * @var UserImageServiceContract
   */
  private $userImageServiceContract;

  /**
   * @var GuzzleClient
   */
  private $httpClient;

  public function __construct(
    StorageServiceContract $storageService,
    UserImageServiceContract $userImageService,
    GuzzleClient $httpClient
  ) {
    $this->storageService = $storageService;
    $this->userImageService = $userImageService;
    $this->httpClient = $httpClient;
  }

  /**
   * User upload image
   *
   * @param string        $userId
   * @param UploadedFile  $image
   *
   * @return \stdClass    properties as below:
   *                      - imageId string    image id
   *                      - url string        url for public access
   */
  public function upload(string $userId, UploadedFile $image) : \stdClass
  {
    $currentImageCount = $this->userImageService->countUserGalleryImage($userId);
    if ($currentImageCount >= config('image.user_upload_max')) {
      throw new UserImageTooManyException();
    }

    $uri = $this->storageService->store($image->path(), [
        'prefix'  => sprintf('pizzas/images-origin/%s', $userId),
        'mime'    => $image->getClientMimeType(),
    ]);

    $imageId = $this->userImageService->addUserImage($userId, $uri);
    if ( ! $imageId) {
      $this->storageService->remove($uri);
      throw new UserImageUploadFailedException();
    }

    $imageUrl = $this->storageService->toHttpUrl($uri);
    event(new EventUserImageUploaded($imageUrl));

    return (object) [
      'imageId'   => $imageId,
      'url'       => $imageUrl,
    ];
  }

  /**
   * User delete his/her images
   */
  public function delete(string $userId, array $imageIds)
  {
    $imageUris = $this->userImageService->deleteUserImages($userId, $imageIds);
    if (empty($imageUris)) {
      throw new AppException('delete images failed');
    }

    $self = $this;
    $imageUris->each(function ($uri, $_) use ($self) {
      $self->storageService->remove($uri);
    });
  }

  /**
   * Get user gallery
   *
   * @param string $userId
   * @param string $imageId   for pagination
   * @param bool $isNext      for pagination
   * @param int $size         for pagination
   *
   * @return Collection       elements are \stdClass, properties as below:
   *                          - imageId string        image id
   *                          - url string            url for public access
   *                          - isAvatar bool         indicate whether this image is user avatar
   */
  public function getUserGallery(string $userId, ?string $imageId, bool $isNext, int $size) : Collection
  {
    $gallery = $this->userImageService->getGallery($userId, $imageId, $isNext, $size); 

    $self = $this;
    return $gallery->map(function ($image, $_) use ($self) {
      return (object) [
        'imageId'   => $image->imageId,
        'url'       => $self->storageService->toHttpUrl($image->uri),
        'isAvatar'  => $image->isAvatar,
      ];
    });
  }

  /**
   * Set user avatar
   *
   * @param string $userId
   * @param string $imageId
   */
  public function setUserAvatar(string $userId, string $imageId)
  {
    $this->userImageService->setAvatar($userId, $imageId);
  }

  /**
   * Request image tailor service to make thumb images
   */
  public function requestTailorUserImaage(string $imageOrigUrl)
  {
    $imageTailorUrl = config('image.tailor_service_url');

    try {
      $res = $this->httpClient
                  ->request('POST', $imageTailorUrl, [
                    'form_params' => [
                      'origUrl' => $imageOrigUrl,
                    ]
                  ]);
      if ($res->getStatusCode() == '200') {
        Log::info('image: tailor request accept', [
                  'origUrl' => $imageOrigUrl
                  ]);
      } else {
        Log::error('image: tailor request reject', [
                  'origUrl' => $imageOrigUrl,
                  'rescode' => $res->getStatusCode(),
                  'message' => $res->getBody()->read(100),
                  ]);
      }
    } catch (\Exception $ex) {
        Log::error('image tailor request throw exception', [
                  'origUrl' => $imageOrigUrl,
                  'message' => $ex->getMessage(),
                  ]);
    }
  }
}
