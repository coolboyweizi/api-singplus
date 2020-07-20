<?php

namespace SingPlus\Domains\Helps\Services;

use SingPlus\Contracts\Helps\Services\HelpService as HelpServiceContract;
use SingPlus\Domains\Helps\Models\Feedback;

class HelpService implements HelpServiceContract
{
  /**
   * @see \SingPlus\Contracts\Helps\Services\HelpService::commitFeedback
   */
  public function commitFeedback(?string $userId, string $message, ?string $countryAbbr)
  {
    $feedback = (new Feedback)->fill([
                  'user_id'       => $userId,
                  'type'          => Feedback::TYPE_GLOBAL,
                  'message'       => [
                                      'content' => $message,
                                    ],
                  'status'        => Feedback::STATUS_WAIT,
                  'country_abbr'  => $countryAbbr,
                ]);
    $feedback->save();
  }

  /**
   * {@inheritdoc}
   */
  public function commitMusicSearchFeedback(
    string $userId,
    string $musicName,
    string $artistName,
    string $language,
    string $other,
    ?string $countryAbbr
  ) {
    $feedback = (new Feedback)->fill([
                  'user_id'       => $userId,
                  'type'          => Feedback::TYPE_MUSIC_SEARCH,
                  'message'       => [
                                      'musicName'   => $musicName,
                                      'artistName'  => $artistName,
                                      'language'    => $language,
                                      'content'     => $other,
                                    ],
                  'status'        => Feedback::STATUS_WAIT,
                  'country_abbr'  => $countryAbbr,
                ]);
    return $feedback->save();
  }

  /**
   * {@inheritdoc}
   */
  public function commitMusicSearchAutoFeedback(string $userId, string $search) : bool
  {
    $feedback = (new Feedback)->fill([
                  'user_id' => $userId,
                  'type'    => Feedback::TYPE_MUSIC_SEARCH_AUTO,
                  'message' => [
                                'musicName' => $search,
                               ],
                  'status'  => Feedback::STATUS_WAIT,
                ]);
    return $feedback->save();
  }

  /**
   * {@inheritdoc}
   */
  public function commitAccompanimentFeedback(
    string $userId,
    string $musicId,
    string $musicName,
    string $artistName,
    string $accompanimentVersion,
    int $type,
    ?string $countryAbbr
  ) : bool {
    $feedback = (new Feedback)->fill([
                  'user_id'       => $userId,
                  'type'          => Feedback::TYPE_ACCOMPANIMENT,
                  'message'       => [
                                      'musicId'     => $musicId,
                                      'musicName'   => $musicName,
                                      'artistName'  => $artistName,
                                      'accompanimentVersion'  => $accompanimentVersion,
                                      'type'        => $type,
                                     ],
                  'status'        => Feedback::STATUS_WAIT,
                  'country_abbr'  => $countryAbbr,
                ]);
    return $feedback->save();
  }
}
