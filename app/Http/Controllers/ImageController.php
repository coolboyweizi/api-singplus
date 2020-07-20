<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\ImageService;

class ImageController extends Controller
{
  /**
   * User upload image
   */
  public function upload(
    Request $request,
    ImageService $imageService
  ) {
    $this->validate($request, [
      'image' => 'required|mimes:jpeg,png,jpg|max:20480',
    ]);

    $uploadFile = $request->file('image');
    $image = $imageService->upload(
      $request->user()->id,
      $request->file('image')
    );

    return $this->renderInfo('success', [
      'imageId' => $image->imageId,
      'url'     => $image->url
    ]);
  }

  /**
   * User delete his/her image by id
   */
  public function delete(
    Request $request,
    ImageService $imageService
  ) {
    $this->validate($request, [
      'imageIds'    => 'required|array|max:5',    // imageIds can not be empey
      'imageIds.*'  => 'uuid',                    // elements in imageIds validation rule
    ]);

    $imageService->delete(
      $request->user()->id,
      $request->request->get('imageIds')
    );

    return $this->renderInfo('success');
  }

  /**
   * User delete image
   */
  public function setUserAvatar(
    Request $request,
    ImageService $imageService
  ) {
    $this->validate($request, [
      'imageId' => 'required|uuid',
    ]);

    $imageService->setUserAvatar(
      $request->user()->id,
      $request->request->get('imageId')
    );

    return $this->renderInfo('success');
  }

  /**
   * Get user gallery
   */
  public function getGallery(
    Request $request,
    ImageService $imageService
  ) {
    $this->validate($request, [
      'userId'  => 'uuid',
      'imageId' => 'uuid|required_with:isNext',
      'isNext'  => 'boolean',
      'size'    => 'integer|min:1|max:50',
    ]);

    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";

    $gallery = $imageService->getUserGallery(
      $request->query->get('userId', $actingUserId),
      $request->query->get('imageId'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', 15)
    );

    return $this->render('users.gallery', [
      'gallery' => $gallery,
    ]);
  }
}
