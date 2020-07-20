<?php

namespace SingPlus\Contracts\Musics\Services;

use Illuminate\Support\Collection;

interface StyleService
{
  /**
   * Get music styles
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                            - id ?string    style id, null stands for other style
   *                            - cover string  style cover image url
   *                            - name string   style name
   *                            - totalNum int  total music number
   */
  public function getStyles() : Collection;

  /**
   * Get other styles
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                            - id string     style id
   */
  public function getOtherStyles() : Collection;

  /**
   * Update style adjust data
   */
  public function updateStyleAdjust();
}
