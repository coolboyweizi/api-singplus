<?php

namespace SingPlus\Contracts\Users\Models;

interface UserProfile
{
  /**
   * @return string
   */
  public function getUserId() : string;

  /**
   * @return string
   */
  public function getNickname() : ?string;

  /**
   * @return int  please see \SingPlus\Contracts\Users\Constants::GENDER_XXX
   */
  public function getGender() : ?string;

  /**
   * @return string   user custom signature
   */
  public function getSignature() : ?string;

  /**
   * @return ?string  user birth date. Y-m-d
   */
  public function getBirthDate() : ?string;

  /**
   * Get follower count
   */
  public function countFollowers() : int;

  /**
   * Count how many users who are followed by current user
   */
  public function countFollowings() : int;
}
