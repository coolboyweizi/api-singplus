<?php

namespace SingPlus\Support;

use LeadThread\GoogleShortener\Google;
use SingPlus\Contracts\Supports\UrlShortener as UrlShortenerContract;

class UrlShortener implements UrlShortenerContract
{
  /**
   * @var LeadThread\GoogleShortener\Google
   */
  private $client;

  public function __construct()
  {
    $appKey = config('google.app_key');
    $this->client = new Google($appKey);
  }

  /**
   * {@inheritdoc}
   */
  public function shorten(string $url) : string
  {
    return $this->client->shorten($url);
  }
}
