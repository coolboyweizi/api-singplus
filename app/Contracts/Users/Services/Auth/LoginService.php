<?php

namespace SingPlus\Contracts\Users\Services\Auth;

interface LoginService
{
  /**
   * user login
   *
   * @param string $mobile    mobile which has country code part
   * @param string $password
   * @param ?bool $remember   user login status will be keep indefinitely,
   *                          or until manually logout, if this value is true
   */
  public function login(string $mobile, string $password, bool $remember) : bool;

  /**
   * user login from user id
   *
   * @param string $userId
   * @param ?bool $remember   user login status will be keep indefinitely,
   *                          or until manually logout, if this value is true
   *
   * @return \stdClass        properties as below:
   *                          - userId string
   */
  public function loginUsingUserId(string $userId, bool $remember) : \stdClass;

  /**
   * user logout
   */
  public function logout();
}
