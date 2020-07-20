<?php

namespace SingPlus\Contracts\Supports;

interface UrlShortener
{
  /**
   * Shorten a normal url to short url
   *
   * @param string $url   original url
   *
   * @return string       shorten url
   */
  public function shorten(string $url) : string;
}
