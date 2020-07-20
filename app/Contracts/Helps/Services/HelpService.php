<?php

namespace SingPlus\Contracts\Helps\Services;

interface HelpService
{
  /**
   * User commit feedback
   *
   * @param ?string $userId
   * @param string $message     feedback content
   * @param ?string $countryAbbr
   */
  public function commitFeedback(?string $userId, string $message, ?string $countryAbbr);

  /**
   * User commit music search feedback
   *
   * @param string $userId
   * @param string $musicName
   * @param string $artistName
   * @param string $language
   * @param string $other
   * @param ?string $countryAbbr
   */
  public function commitMusicSearchFeedback(
    string $userId,
    string $musicName,
    string $artistName,
    string $language,
    string $other,
    ?string $countryAbbr
  );

  /**
   * System commit music search nothing feedback
   *
   * @param string $userId
   * @param string $search
   *
   * @return bool
   */
  public function commitMusicSearchAutoFeedback(string $userId, string $search) : bool;

  /**
   * System commit music accompaniment feedback
   *
   * @param string $userId
   * @param string $musicId
   * @param string $musicName
   * @param string $artistName
   * @param string $accompanimentVersion
   * @param int $type,
   * @param ?string $countryAbbr
   */
  public function commitAccompanimentFeedback(
    string $userId,
    string $musicId,
    string $musicName,
    string $artistName,
    string $accompanimentVersion,
    int $type,
    ?string $countryAbbr
  ) : bool;
}
