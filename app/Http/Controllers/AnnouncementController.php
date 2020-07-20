<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\AnnouncementService;

class AnnouncementController extends Controller
{
  /**
   * Fetch announcements list
   */
  public function listAnnouncements(
    Request $request,
    AnnouncementService $announcementService
  ) {
    $this->validate($request, [
      'announcementId'  => 'uuid|required_with:isNext',
      'isNext'          => 'boolean',
      'size'            => 'integer|min:1|max:50',
    ]);
    $countryAbbr = $request->headers->get('X-CountryAbbr');

    $announcements = $announcementService->getAnnouncements(
      $request->query->get('announcementId'),
      (bool) $request->query->get('isNext', true),
      $request->query->get('size', 10),
      $countryAbbr
    );

    return $this->render('messages.announcements', [
      'announcements' => $announcements,
    ]);
  }
}
