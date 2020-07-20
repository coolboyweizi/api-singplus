<?php

namespace SingPlus\Contracts\Musics\Services;

use Illuminate\Support\Collection;

interface LanguageService
{
  /**
   * Get music styles
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                            - id string     language id
   *                            - cover string  language cover image url
   *                            - name string   language name
   *                            - totalNum int  total music number
   */
  public function getLanguages() : Collection;

  /**
   * Update language adjust data
   */
  public function updateLanguageAdjust();
}
